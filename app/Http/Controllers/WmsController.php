<?php

namespace App\Http\Controllers;

use App\Infrastructure\Models\WmsLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use SimpleXMLElement;

class WmsController extends Controller
{
    public function index()
    {
        $wmsLinks = WmsLink::withCount('layers')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'wmsLinks' => $wmsLinks,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate(
            [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    'unique:wms_links,name',
                ],
                'url' => [
                    'required',
                    'url',
                    'max:1000',
                ],
                'version' => [
                    'nullable',
                    'string',
                    'max:20',
                ],
            ],
            [
                'name.required' => 'Informe o nome do link WMS.',
                'name.unique' => 'Já existe um link WMS com esse nome.',
                'url.required' => 'Informe a URL do WMS.',
                'url.url' => 'Informe uma URL válida.',
            ]
        );

        $wmsLink = WmsLink::create([
            'name' => trim($validated['name']),
            'url' => trim($validated['url']),
            'version' => $validated['version'] ?? '1.3.0',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Link WMS criado com sucesso.',
            'wmsLink' => $wmsLink,
        ], 201);
    }

    public function show($id)
    {
        $wmsLink = WmsLink::withCount('layers')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'wmsLink' => $wmsLink,
        ]);
    }

    public function update(Request $request, $id)
    {
        $wmsLink = WmsLink::findOrFail($id);

        $validated = $request->validate(
            [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('wms_links', 'name')->ignore($wmsLink->id),
                ],
                'url' => [
                    'required',
                    'url',
                    'max:1000',
                ],
                'version' => [
                    'nullable',
                    'string',
                    'max:20',
                ],
            ],
            [
                'name.required' => 'Informe o nome do link WMS.',
                'name.unique' => 'Já existe um link WMS com esse nome.',
                'url.required' => 'Informe a URL do WMS.',
                'url.url' => 'Informe uma URL válida.',
            ]
        );

        $wmsLink->update([
            'name' => trim($validated['name']),
            'url' => trim($validated['url']),
            'version' => $validated['version'] ?? '1.3.0',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Link WMS atualizado com sucesso.',
            'wmsLink' => $wmsLink,
        ]);
    }

    public function destroy($id)
    {
        $wmsLink = WmsLink::withCount('layers')
            ->findOrFail($id);

        if ($wmsLink->layers_count > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível excluir um link WMS que possui camadas vinculadas.',
            ], 422);
        }

        $wmsLink->delete();

        return response()->json([
            'success' => true,
            'message' => 'Link WMS excluído com sucesso.',
        ]);
    }

    public function getWmsLayersByLink($wmsLinkId)
    {
        $wmsLink = WmsLink::findOrFail($wmsLinkId);

        $capabilitiesUrl = $this->buildCapabilitiesUrl(
            $wmsLink->url,
            $wmsLink->version ?? '1.3.0'
        );

        try {
            $response = Http::withOptions([
                    'connect_timeout' => 15,
                    'timeout' => 90,
                ])
                ->retry(2, 1500)
                ->accept('*/*')
                ->get($capabilitiesUrl);

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não foi possível acessar o GetCapabilities do WMS.',
                    'status' => $response->status(),
                    'url' => $capabilitiesUrl,
                ], 422);
            }

            $xmlContent = $response->body();

            $layers = $this->extractLayersFromCapabilities($xmlContent);

            return response()->json([
                'success' => true,
                'wmsLink' => [
                    'id' => $wmsLink->id,
                    'name' => $wmsLink->name,
                    'url' => $wmsLink->url,
                    'version' => $wmsLink->version,
                ],
                'capabilities_url' => $capabilitiesUrl,
                'total' => count($layers),
                'layers' => $layers,
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao consultar o WMS: ' . $exception->getMessage(),
                'url' => $capabilitiesUrl,
            ], 500);
        }
    }

    private function buildCapabilitiesUrl(string $url, string $version): string
    {
        $separator = str_contains($url, '?') ? '&' : '?';

        return $url . $separator . http_build_query([
            'service' => 'WMS',
            'request' => 'GetCapabilities',
            'version' => $version ?: '1.3.0',
        ]);
    }

    private function extractLayersFromCapabilities(string $xmlContent): array
    {
        libxml_use_internal_errors(true);

        $xml = simplexml_load_string(
            $xmlContent,
            SimpleXMLElement::class,
            LIBXML_NONET
        );

        if (!$xml) {
            return [];
        }

        $layerNodes = $xml->xpath('//*[local-name()="Layer"][*[local-name()="Name"]]');

        if (!$layerNodes) {
            return [];
        }

        $layers = [];

        foreach ($layerNodes as $layerNode) {
            $name = $this->getFirstChildText($layerNode, 'Name');

            if (!$name) {
                continue;
            }

            $title = $this->getFirstChildText($layerNode, 'Title') ?: $name;
            $abstract = $this->getFirstChildText($layerNode, 'Abstract');
            $crs = $this->extractCrs($layerNode);
            $legendUrl = $this->extractLegendUrl($layerNode);

            $layers[] = [
                'name' => $name,
                'title' => $title,
                'abstract' => $abstract,
                'crs' => $crs,
                'legend_url' => $legendUrl,
            ];
        }

        return collect($layers)
            ->unique('name')
            ->sortBy('title')
            ->values()
            ->all();
    }

    private function getFirstChildText(SimpleXMLElement $node, string $childName): ?string
    {
        $result = $node->xpath('./*[local-name()="' . $childName . '"]');

        if (!$result || !isset($result[0])) {
            return null;
        }

        $value = trim((string) $result[0]);

        return $value !== '' ? $value : null;
    }

    private function extractCrs(SimpleXMLElement $layerNode): ?string
    {
        $crsNodes = $layerNode->xpath('./*[local-name()="CRS"]');

        if (!$crsNodes || count($crsNodes) === 0) {
            $crsNodes = $layerNode->xpath('./*[local-name()="SRS"]');
        }

        if (!$crsNodes || count($crsNodes) === 0) {
            return null;
        }

        return trim((string) $crsNodes[0]) ?: null;
    }

    private function extractLegendUrl(SimpleXMLElement $layerNode): ?string
    {
        $legendNodes = $layerNode->xpath('.//*[local-name()="LegendURL"]/*[local-name()="OnlineResource"]');

        if (!$legendNodes || !isset($legendNodes[0])) {
            return null;
        }

        $onlineResource = $legendNodes[0];

        $xlinkAttributes = $onlineResource->attributes('http://www.w3.org/1999/xlink');

        if (isset($xlinkAttributes['href'])) {
            return (string) $xlinkAttributes['href'];
        }

        $attributes = $onlineResource->attributes();

        if (isset($attributes['href'])) {
            return (string) $attributes['href'];
        }

        return null;
    }
}