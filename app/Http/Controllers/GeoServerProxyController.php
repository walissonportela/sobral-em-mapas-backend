<?php

namespace App\Http\Controllers;

use App\Application\Services\GeoServerService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class GeoServerProxyController extends Controller
{
    protected GeoServerService $geoServerService;

    public function __construct(GeoServerService $geoServerService)
    {
        $this->geoServerService = $geoServerService;
    }

    public function proxyWms(Request $request)
    {
        $params = $request->all();

        $response = $this->geoServerService->getWmsLayer($params);

        if (!$response->successful()) {
            return $this->corsJsonResponse([
                'message' => 'Erro ao carregar a camada WMS do GeoServer.',
                'status' => $response->status(),
            ], 500);
        }

        if (empty($response->body())) {
            return $this->corsJsonResponse([
                'message' => 'Problema ao carregar a camada. Verifique no GeoServer.',
            ], 500);
        }

        $contentType = $response->header('Content-Type') ?: 'image/png';

        return $this->corsImageResponse(
            $response->body(),
            $contentType,
            300
        );
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

        $response = $this->geoServerService->getLegendGraphic(
            $layer,
            $wmsLinkId
        );

        if (!$response->successful()) {
            return $this->corsJsonResponse([
                'error' => 'Failed to retrieve legend graphic',
                'status' => $response->status(),
            ], 500);
        }

        if (empty($response->body())) {
            return $this->corsJsonResponse([
                'message' => 'Legenda vazia retornada pelo GeoServer.',
            ], 500);
        }

        $contentType = $response->header('Content-Type') ?: 'image/png';

        return $this->corsImageResponse(
            $response->body(),
            $contentType,
            3600
        );
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

    private function corsJsonResponse(
        array $data,
        int $status = 200
    ) {
        return response()->json($data, $status)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
            ->header('Access-Control-Expose-Headers', 'Content-Type, Cache-Control')
            ->header('Cross-Origin-Resource-Policy', 'cross-origin');
    }
}