<?php

namespace Tests\Unit;

use App\Security\AllowedOutboundWms;
use InvalidArgumentException;
use Tests\TestCase;

class AllowedOutboundWmsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.wms_allowed_hosts', 'geoserver.sobral.ce.gov.br');
    }

    public function test_allows_known_wms_endpoint(): void
    {
        $url = AllowedOutboundWms::assertAllowed('http://geoserver.sobral.ce.gov.br/geoserver/ows');
        $this->assertSame('http://geoserver.sobral.ce.gov.br/geoserver/ows', $url);
    }

    /**
     * @dataProvider blockedWmsUrls
     */
    public function test_blocks_untrusted_wms_destinations(string $url): void
    {
        $this->expectException(InvalidArgumentException::class);
        AllowedOutboundWms::assertAllowed($url);
    }

    public static function blockedWmsUrls(): array
    {
        return [
            ['http://127.0.0.1/latest/meta-data'],
            ['http://169.254.169.254/latest/meta-data'],
            ['http://localhost:8080/private'],
            ['file:///etc/passwd'],
            ['https://user:password@geoserver.sobral.ce.gov.br/geoserver/ows'],
            ['https://evil.example/geoserver/ows'],
            ['https://geoserver.sobral.ce.gov.br.evil.example/ows'],
            ['https://geoserver.sobral.ce.gov.br:8080/ows'],
        ];
    }
}
