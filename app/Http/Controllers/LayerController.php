<?php

namespace App\Http\Controllers;

use App\Infrastructure\Models\Layer;
use App\Infrastructure\Models\Subcategory;
use App\Infrastructure\Models\WmsLink;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;

class LayerController extends Controller
{
    public function index()
    {
        $layers = Layer::with([
                'subcategory.category',
                'wmsLink',
            ])
            ->orderByDesc('is_active')
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        $subcategories = Subcategory::with('category')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        $wmsLinks = WmsLink::orderBy('name')->get();

        return response()->json([
            'success' => true,
            'layers' => $layers,
            'subcategories' => $subcategories,
            'wmsLinks' => $wmsLinks,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateLayer($request);
        $sourceFormat = $validated['source_format'];
        $isGeoJson = $sourceFormat === 'GEOJSON';

        $layer = Layer::create([
            'name' => trim($validated['name']),
            'layer_name' => trim($validated['layer_name']),
            'crs' => $validated['crs'] ?? 'EPSG:4326',
            'legend_url' => $validated['legend_url'] ?? null,
            'type' => $isGeoJson ? null : ($validated['type'] ?? 'WMS'),
            'description' => $validated['description'] ?? null,
            'order' => $validated['order'] ?? 0,
            'subcategory' => $validated['subcategory'] ?? null,
            'image_path' => $validated['image_path'] ?? null,
            'max_scale' => $validated['max_scale'] ?? null,
            'symbol' => $validated['symbol'] ?? null,
            'wms_link_id' => $isGeoJson ? null : ($validated['wms_link_id'] ?? null),
            'source_format' => $sourceFormat,
            'geojson_path' => $isGeoJson ? $validated['geojson_path'] : null,
            'isPublic' => $request->boolean('isPublic', true),
            'is_active' => true,
        ]);

        $layer->load([
            'subcategory.category',
            'wmsLink',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Camada criada com sucesso.',
            'layer' => $layer,
        ], 201);
    }

    public function show($id)
    {
        $layer = Layer::with([
                'subcategory.category',
                'wmsLink',
            ])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'layer' => $layer,
        ]);
    }

    public function update(Request $request, $id)
    {
        $layer = Layer::findOrFail($id);

        // Se o formato não vier da interface, preserva o formato atual.
        $validated = $this->validateLayer(
            $request,
            $layer->id,
            $layer->source_format ?? 'WMS'
        );

        $sourceFormat = $validated['source_format'];
        $isGeoJson = $sourceFormat === 'GEOJSON';

        $layer->update([
            'name' => trim($validated['name']),
            'layer_name' => trim($validated['layer_name']),
            'crs' => $validated['crs'] ?? 'EPSG:4326',
            'legend_url' => $validated['legend_url'] ?? null,
            'type' => $isGeoJson ? null : ($validated['type'] ?? 'WMS'),
            'description' => $validated['description'] ?? null,
            'order' => $validated['order'] ?? 0,
            'subcategory' => $validated['subcategory'] ?? null,
            'image_path' => $validated['image_path'] ?? null,
            'max_scale' => $validated['max_scale'] ?? null,
            'symbol' => $validated['symbol'] ?? null,
            'wms_link_id' => $isGeoJson ? null : ($validated['wms_link_id'] ?? null),
            'source_format' => $sourceFormat,
            'geojson_path' => $isGeoJson ? $validated['geojson_path'] : null,
            'isPublic' => $request->boolean('isPublic', true),
        ]);

        $layer->load([
            'subcategory.category',
            'wmsLink',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Camada atualizada com sucesso.',
            'layer' => $layer,
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate(
            [
                'is_active' => ['required', 'boolean'],
            ],
            [
                'is_active.required' => 'Informe o status da camada.',
                'is_active.boolean' => 'O status da camada é inválido.',
            ]
        );

        $layer = Layer::findOrFail($id);

        $layer->update([
            'is_active' => $request->boolean('is_active'),
        ]);

        $layer->load([
            'subcategory.category',
            'wmsLink',
        ]);

        return response()->json([
            'success' => true,
            'message' => $layer->is_active
                ? 'Camada ativada com sucesso.'
                : 'Camada desativada com sucesso.',
            'layer' => $layer,
        ]);
    }

    public function destroy($id)
    {
        $layer = Layer::findOrFail($id);
        $layer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Camada excluída definitivamente com sucesso.',
        ]);
    }

    private function validateLayer(
        Request $request,
        $ignoreId = null,
        $defaultSourceFormat = 'WMS'
    ): array {
        // Permite que formulários antigos continuem enviando apenas campos WMS.
        $request->merge([
            'source_format' => $request->input('source_format') ?: $defaultSourceFormat,
        ]);

        $validated = $request->validate(
            [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('layers', 'name')->ignore($ignoreId),
                ],
                'layer_name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('layers', 'layer_name')->ignore($ignoreId),
                ],
                'source_format' => [
                    'required',
                    Rule::in(['WMS', 'GEOJSON']),
                ],
                'geojson_path' => [
                    'required_if:source_format,GEOJSON',
                    'nullable',
                    'string',
                    'max:500',
                    'regex:~^/(?:api/)?geodata/[A-Za-z0-9_-]+\.geojson$~',
                ],
                'crs' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
                'legend_url' => [
                    'nullable',
                    'string',
                    'max:500',
                ],
                'type' => [
                    'nullable',
                    Rule::in(['WMS', 'WFS']),
                ],
                'description' => [
                    'nullable',
                    'string',
                ],
                'order' => [
                    'nullable',
                    'integer',
                ],
                'subcategory' => [
                    'nullable',
                    'integer',
                    'exists:subcategories,id',
                ],
                'image_path' => [
                    'nullable',
                    'string',
                    'max:500',
                ],
                'max_scale' => [
                    'nullable',
                    'numeric',
                ],
                'symbol' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
                'wms_link_id' => [
                    'nullable',
                    'integer',
                    'exists:wms_links,id',
                ],
                'isPublic' => [
                    'nullable',
                    'boolean',
                ],
            ],
            [
                'name.required' => 'Informe o nome da camada.',
                'name.unique' => 'Já existe uma camada com esse nome.',
                'layer_name.required' => 'Informe o nome técnico da camada.',
                'layer_name.unique' => 'Já existe uma camada com esse nome técnico.',
                'source_format.in' => 'O formato da camada deve ser WMS ou GEOJSON.',
                'geojson_path.required_if' => 'Informe o caminho do arquivo GeoJSON.',
                'geojson_path.regex' => 'Use /geodata/arquivo.geojson ou /api/geodata/arquivo.geojson.',
                'subcategory.exists' => 'A subcategoria selecionada é inválida.',
                'wms_link_id.exists' => 'O link WMS selecionado é inválido.',
                'type.in' => 'O tipo de serviço deve ser WMS ou WFS.',
                'isPublic.boolean' => 'O status público da camada é inválido.',
            ]
        );

        // Para uploads do Laravel, garante que o arquivo realmente existe
        // antes de registrar a camada no banco de dados.
        $geojsonPath = $validated['geojson_path'] ?? null;
        if (
            ($validated['source_format'] ?? '') === 'GEOJSON' &&
            is_string($geojsonPath) &&
            str_starts_with($geojsonPath, '/api/geodata/')
        ) {
            $filename = basename($geojsonPath);
            if (!Storage::disk('local')->exists('geodata/' . $filename)) {
                throw ValidationException::withMessages([
                    'geojson_path' => 'O arquivo GeoJSON não foi encontrado no armazenamento do Laravel.',
                ]);
            }
        }

        return $validated;
    }
}
