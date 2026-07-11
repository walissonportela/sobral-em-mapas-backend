<?php

namespace App\Http\Controllers;

use App\Application\Services\GeoServerService;
use Illuminate\Http\Request;

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
            return response()->json([
                'message' => 'Erro ao carregar a camada WMS do GeoServer.',
                'status' => $response->status(),
            ], 500);
        }

        if (empty($response->body())) {
            return response()->json([
                'message' => 'Problema ao carregar a camada. Verifique no GeoServer.',
            ], 500);
        }

        $contentType = $response->header('Content-Type') ?: 'image/png';

        return response($response->body(), 200)
            ->header('Content-Type', $contentType)
            ->header('Cache-Control', 'public, max-age=300');
    }

    public function getLegendGraphic(Request $request)
    {
        $layer = $request->input('layer');
        $wmsLinkId = $request->input('wms_link_id');

        if (!$layer) {
            return response()->json([
                'message' => 'Informe a layer da legenda.',
            ], 422);
        }

        $response = $this->geoServerService->getLegendGraphic(
            $layer,
            $wmsLinkId
        );

        if (!$response->successful()) {
            return response()->json([
                'error' => 'Failed to retrieve legend graphic',
                'status' => $response->status(),
            ], 500);
        }

        $contentType = $response->header('Content-Type') ?: 'image/png';

        return response($response->body(), 200)
            ->header('Content-Type', $contentType)
            ->header('Cache-Control', 'public, max-age=3600');
    }
}