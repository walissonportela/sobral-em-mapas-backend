<?php

namespace App\Infrastructure\Adapters;

use App\Infrastructure\Models\WmsLink;
use App\Security\AllowedOutboundWms;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class GeoServerClient
{
    private string $defaultBaseUrl = 'http://geoserver.sobral.ce.gov.br/geoserver/ows';
    private string $defaultVersion = '1.1.1';

    public function getWms(array $params)
    {
        $wmsLink = $this->resolveWmsLink($params['wms_link_id'] ?? null);
        $url = AllowedOutboundWms::assertAllowed($wmsLink?->url ?: $this->defaultBaseUrl);

        $params = array_intersect_key($params, array_flip([
            'layers', 'styles', 'bbox', 'width', 'height', 'srs', 'crs',
            'format', 'transparent', 'version', 'bgcolor', 'exceptions'
        ]));

        $params['service'] = 'WMS';
        $params['request'] = 'GetMap';
        $params['version'] = $params['version'] ?? $wmsLink?->version ?? $this->defaultVersion;
        $params['format'] = 'image/png';
        $params['transparent'] = $params['transparent'] ?? 'true';

        $this->assertMapParameters($params);

        return $this->http()->get($url, $params);
    }

    public function getLegendGraphic(string $layer, $wmsLinkId = null)
    {
        if (!preg_match('/\A[a-zA-Z0-9_.:-]{1,160}\z/D', $layer)) {
            throw new InvalidArgumentException('Nome de camada WMS inválido.');
        }

        $wmsLink = $this->resolveWmsLink($wmsLinkId);
        $url = AllowedOutboundWms::assertAllowed($wmsLink?->url ?: $this->defaultBaseUrl);

        return $this->http()->get($url, [
            'service' => 'WMS',
            'request' => 'GetLegendGraphic',
            'version' => $wmsLink?->version ?: $this->defaultVersion,
            'format' => 'image/png',
            'layer' => $layer,
        ]);
    }

    private function http()
    {
        // Nunca segue um redirecionamento que poderia apontar para IP interno.
        return Http::withOptions([
            'connect_timeout' => 3,
            'allow_redirects' => false,
        ])->timeout(10)->accept('image/png');
    }

    private function assertMapParameters(array $params): void
    {
        $width = filter_var($params['width'] ?? null, FILTER_VALIDATE_INT);
        $height = filter_var($params['height'] ?? null, FILTER_VALIDATE_INT);
        if ($width === false || $height === false || $width < 1 || $height < 1
            || $width > 2048 || $height > 2048
        ) {
            throw new InvalidArgumentException('Dimensões WMS inválidas.');
        }

        $bbox = explode(',', (string) ($params['bbox'] ?? ''));
        if (count($bbox) !== 4 || count(array_filter($bbox, static function ($v) {
            return is_numeric(trim($v)) && is_finite((float) trim($v));
        })) !== 4) {
            throw new InvalidArgumentException('BBOX WMS inválida.');
        }

        if (!is_string($params['layers'] ?? null) ||
            !preg_match('/\A[a-zA-Z0-9_.:,-]{1,400}\z/D', $params['layers'])
        ) {
            throw new InvalidArgumentException('Camadas WMS inválidas.');
        }

        foreach (['srs', 'crs', 'version', 'styles', 'transparent', 'bgcolor', 'exceptions'] as $key) {
            if (isset($params[$key]) &&
                (!is_string($params[$key]) || strlen($params[$key]) > 160 ||
                    preg_match('/[\x00-\x1f\x7f]/', $params[$key]))) {
                throw new InvalidArgumentException('Parâmetro WMS inválido.');
            }
        }
    }

    private function resolveWmsLink($wmsLinkId): ?WmsLink
    {
        if ($wmsLinkId === null || $wmsLinkId === '') return null;
        if (!ctype_digit((string) $wmsLinkId)) {
            throw new InvalidArgumentException('Identificador WMS inválido.');
        }
        return WmsLink::findOrFail((int) $wmsLinkId);
    }
}
