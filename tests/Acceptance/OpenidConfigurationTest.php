<?php

namespace Dontdrinkandroot\OpenIdBundle\Tests\Acceptance;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class OpenidConfigurationTest extends KernelTestCase
{
    public function testContainsRequiredResponseTypes(): void
    {
        $metadata = $this->fetchDiscoveryDocument();

        self::assertSame(['code'], $metadata['response_types_supported']);
    }

    public function testContainsRequiredIdTokenSigningAlgorithms(): void
    {
        $metadata = $this->fetchDiscoveryDocument();

        self::assertSame(['RS256'], $metadata['id_token_signing_alg_values_supported']);
    }

    public function testExposesScopesUnderStandardKey(): void
    {
        $metadata = $this->fetchDiscoveryDocument();

        self::assertSame(['openid', 'profile', 'api'], $metadata['scopes_supported']);
        self::assertArrayNotHasKey('supported_scopes', $metadata);
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchDiscoveryDocument(): array
    {
        $kernel = self::bootKernel();
        $response = $kernel->handle(Request::create('http://localhost/.well-known/openid-configuration'));

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(200, $response->getStatusCode());

        $metadata = json_decode((string)$response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($metadata);

        return $metadata;
    }
}
