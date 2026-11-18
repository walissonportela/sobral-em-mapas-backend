<?php

namespace App\Security;

use InvalidArgumentException;

/**
 * Apenas servidores WMS explicitamente autorizados no ambiente podem receber
 * requisições feitas pelo backend. A lista não vem de parâmetros públicos.
 */
final class AllowedOutboundWms
{
    public static function assertAllowed(string $url): string
    {
        $url = trim($url);
        $parts = parse_url($url);
        if (!is_array($parts)
            || empty($parts['scheme'])
            || empty($parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['fragment'])
        ) {
            throw new InvalidArgumentException('Endereço WMS inválido.');
        }

        $scheme = strtolower($parts['scheme']);
        $host = strtolower(rtrim($parts['host'], '.'));
        $port = $parts['port'] ?? null;

        if (!in_array($scheme, ['http', 'https'], true)
            || ($port !== null && !in_array($port, [80, 443], true))
            || filter_var($host, FILTER_VALIDATE_IP)
            || !preg_match('/\A[a-z0-9](?:[a-z0-9.-]*[a-z0-9])?\z/D', $host)
        ) {
            throw new InvalidArgumentException('Endereço WMS não permitido.');
        }

        $allowed = array_filter(array_map(
            static function ($item) {
                return strtolower(rtrim(trim($item), '.'));
            },
            explode(',', (string) config('services.wms_allowed_hosts', 'geoserver.sobral.ce.gov.br'))
        ));

        if (!in_array($host, $allowed, true)) {
            throw new InvalidArgumentException('O servidor WMS não está na lista de hosts permitidos.');
        }

        // Reconstrói a URL sem query string ou credenciais. Parâmetros OGC
        // são montados exclusivamente pelo cliente HTTP do backend.
        $path = $parts['path'] ?? '/';
        if (preg_match('/[\x00-\x1F\x7F]/', $path)) {
            throw new InvalidArgumentException('Caminho WMS inválido.');
        }

        return $scheme . '://' . $host
            . ($port !== null ? ':' . $port : '')
            . $path;
    }
}
