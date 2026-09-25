<?php

namespace Dontdrinkandroot\OpenIdBundle\Tests\Unit\Controller;

use Dontdrinkandroot\OpenIdBundle\Controller\OpenidConfigurationAction;
use LogicException;
use Override;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

final class OpenidConfigurationActionTest extends TestCase
{
    public function testContainsRequiredResponseTypes(): void
    {
        $action = new OpenidConfigurationAction(new FixedUrlGenerator());

        $response = $action(new Request());

        self::assertInstanceOf(JsonResponse::class, $response);
        $metadata = json_decode((string)$response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(['code'], $metadata['response_types_supported']);
    }

    public function testContainsRequiredIdTokenSigningAlgorithms(): void
    {
        $action = new OpenidConfigurationAction(new FixedUrlGenerator());

        $response = $action(new Request());

        self::assertInstanceOf(JsonResponse::class, $response);
        $metadata = json_decode((string)$response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(['RS256'], $metadata['id_token_signing_alg_values_supported']);
    }

    public function testExposesScopesUnderStandardKey(): void
    {
        $action = new OpenidConfigurationAction(new FixedUrlGenerator());

        $response = $action(new Request());

        self::assertInstanceOf(JsonResponse::class, $response);
        $metadata = json_decode((string)$response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(['openid', 'profile', 'api'], $metadata['scopes_supported']);
        self::assertArrayNotHasKey('supported_scopes', $metadata);
    }
}

final class FixedUrlGenerator implements UrlGeneratorInterface
{
    private const ISSUER = 'https://op.example.com';
    /** @var array<string, non-empty-string> */
    private const PATHS = [
        'oauth2_authorize' => '/oauth2/authorize',
        'oauth2_token' => '/oauth2/token',
        'ddr.openid.userinfo' => '/oauth2/userinfo',
        'ddr.openid.logout' => '/oauth2/logout',
        'ddr.openid.jwks' => '/.well-known/jwks.json',
    ];

    // Ignored: parent declares bare `array`; narrowing would break contravariance.
    // @phpstan-ignore missingType.iterableValue
    #[Override]
    public function generate(string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_URL): string
    {
        return self::ISSUER . self::PATHS[$name];
    }

    #[Override]
    public function setContext(RequestContext $context): void
    {
        throw new LogicException('Not expected to be called in this test');
    }

    #[Override]
    public function getContext(): RequestContext
    {
        throw new LogicException('Not expected to be called in this test');
    }
}