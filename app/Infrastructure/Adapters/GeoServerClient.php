<?php

namespace App\Infrastructure\Adapters;

use App\Infrastructure\Models\WmsLink;
use Illuminate\Support\Facades\Http;

class GeoServerClient
{
    private string $defaultBaseUrl = 'http://geoserver.sobral.ce.gov.br/geoserver/ows';

    private string $defaultVersion = '1.1.1';

    public function getWms(array $params)
    {
        $wmsLinkId = $params['wms_link_id'] ?? null;

        $wmsLink = $this->resolveWmsLink($wmsLinkId);

        $baseUrl = $this->normalizeWmsUrl(
            $wmsLink?->url ?: $this->defaultBaseUrl
        );

        $version = $params['version']
            ?? $wmsLink?->version
            ?? $this->defaultVersion;

        unset($params['wms_link_id']);

        $params = $this->cleanParams($params);

        $params['service'] = $params['service'] ?? 'WMS';
        $params['request'] = $params['request'] ?? 'GetMap';
        $params['version'] = $version;
        $params['format'] = $params['format'] ?? 'image/png';
        $params['transparent'] = $params['transparent'] ?? 'true';

        return Http::timeout(30)
            ->accept('*/*')
            ->get($baseUrl, $params);
    }

    public function getLegendGraphic(string $layer, $wmsLinkId = null)
    {
        $wmsLink = $this->resolveWmsLink($wmsLinkId);

        $baseUrl = $this->normalizeWmsUrl(
            $wmsLink?->url ?: $this->defaultBaseUrl
        );

        $version = $wmsLink?->version ?: $this->defaultVersion;

        return Http::timeout(30)
            ->accept('*/*')
            ->get($baseUrl, [
                'service' => 'WMS',
                'request' => 'GetLegendGraphic',
                'version' => $version,
                'format' => 'image/png',
                'layer' => $layer,
            ]);
    }

    private function resolveWmsLink($wmsLinkId): ?WmsLink
    {
        if (!$wmsLinkId) {
            return null;
        }

        return WmsLink::find($wmsLinkId);
    }

    private function cleanParams(array $params): array
    {
        return collect($params)
            ->filter(function ($value) {
                return $value !== null && $value !== '';
            })
            ->all();
    }

    private function normalizeWmsUrl(string $url): string
    {
        $url = trim($url);

        $parts = parse_url($url);

        if (
            !$parts ||
            empty($parts['scheme']) ||
            empty($parts['host'])
        ) {
            return $url;
        }

        $baseUrl = $parts['scheme'] . '://' . $parts['host'];

        if (!empty($parts['port'])) {
            $baseUrl .= ':' . $parts['port'];
        }

        if (!empty($parts['path'])) {
            $baseUrl .= $parts['path'];
        }

        return $baseUrl;
    }
}