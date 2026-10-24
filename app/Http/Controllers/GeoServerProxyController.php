<?php

namespace App\Http\Controllers;

use App\Application\Services\GeoServerService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class GeoServerProxyController extends Controller
{
    protected GeoServerService $geoServerService;

    public function __construct(GeoServerService $geoServerService)
    {
        $this->geoServerService = $geoServerService;
    }

    public function proxyWms(Request $request)
    {
        try {
            $response = $this->geoServerService->getWmsLayer($request->all());
        } catch (ConnectionException $exception) {
            Log::warning('WMS indisponível ao solicitar mapa.', [
                'error' => $exception->getMessage(),
            ]);

            return $this->corsJsonResponse([
                'message' => 'Serviço de mapas WMS temporariamente indisponível.',
            ], 503);
        }

        if (!$response->successful()) {
            return $this->corsJsonResponse([
                'message' => 'O servidor WMS não conseguiu responder à solicitação.',
                'upstream_status' => $response->status(),
            ], 502);
        }

        if ($response->body() === '') {
            return $this->corsJsonResponse([
                'message' => 'O servidor WMS retornou uma resposta vazia.',
            ], 502);
        }

        $contentType = $response->header('Content-Type') ?: 'application/octet-stream';
        if (stripos($contentType, 'image/') !== 0) {
            // Servidores OGC também podem devolver exceções XML com HTTP 200.
            return $this->corsJsonResponse([
                'message' => 'O servidor WMS retornou uma resposta que não é uma imagem.',
            ], 502);
        }

        return $this->corsImageResponse($response->body(), $contentType, 300);
    }

    public function getLegendGraphic(Request $request)
    {
        $layer = $request->input('layer');
        $wmsLinkId = $request->input('wms_link_id');

        if (!$layer) {
            return $this->corsJsonResponse([
                'message' => 'Informe a layer da legenda.',
            ], 422);
        }

        try {
            $response = $this->geoServerService->getLegendGraphic($layer, $wmsLinkId);
        } catch (ConnectionException $exception) {
            Log::warning('WMS indisponível ao solicitar legenda.', [
                'error' => $exception->getMessage(),
            ]);

            return $this->corsJsonResponse([
                'message' => 'Legenda WMS temporariamente indisponível.',
            ], 503);
        }

        if (!$response->successful()) {
            return $this->corsJsonResponse([
                'message' => 'Não foi possível recuperar a legenda do WMS.',
                'upstream_status' => $response->status(),
            ], 502);
        }

        if ($response->body() === '') {
            return $this->corsJsonResponse([
                'message' => 'O servidor WMS retornou uma legenda vazia.',
            ], 502);
        }

        $contentType = $response->header('Content-Type') ?: 'application/octet-stream';
        if (stripos($contentType, 'image/') !== 0) {
            return $this->corsJsonResponse([
                'message' => 'O servidor WMS retornou uma legenda inválida.',
            ], 502);
        }

        return $this->corsImageResponse($response->body(), $contentType, 3600);
    }

    private function corsImageResponse(
        string $body,
        string $contentType = 'image/png',
        int $maxAge = 300
    ): Response {
        return response($body, 200)
            ->header('Content-Type', $contentType)
            ->header('Cache-Control', "public, max-age={$maxAge}")
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
            ->header('Access-Control-Expose-Headers', 'Content-Type, Cache-Control')
            ->header('Cross-Origin-Resource-Policy', 'cross-origin');
    }

    private function corsJsonResponse(array $data, int $status = 200)
    {
        return response()->json($data, $status)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
            ->header('Access-Control-Expose-Headers', 'Content-Type, Cache-Control')
            ->header('Cross-Origin-Resource-Policy', 'cross-origin');
    }
}
