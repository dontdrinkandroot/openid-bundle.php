<?php

namespace Dontdrinkandroot\OpenIdBundle\Tests\Unit\Service\Nonce;

use Dontdrinkandroot\OpenIdBundle\Service\Nonce\CachedNonceService;
use Override;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class CachedNonceServiceTest extends TestCase
{
    private CachedNonceService $nonceService;
    private ArrayAdapter $cacheAdapter;

    #[Override]
    protected function setUp(): void
    {
        $this->cacheAdapter = new ArrayAdapter();
        $this->nonceService = new CachedNonceService($this->cacheAdapter);
    }

    public function testRoundTripsNonceByAuthCodeId(): void
    {
        $this->nonceService->storeNonceByAuthCodeId('auth-code-1', 'nonce-abc');

        self::assertSame('nonce-abc', $this->nonceService->findNonceByAuthCodeId('auth-code-1'));
    }

    public function testFindReturnsNullForUnknownAuthCodeId(): void
    {
        self::assertNull($this->nonceService->findNonceByAuthCodeId('unknown'));
    }

    public function testRemoveNonceByAuthCodeIdDeletesEntry(): void
    {
        $this->nonceService->storeNonceByAuthCodeId('auth-code-1', 'nonce-abc');

        $this->nonceService->removeNonceByAuthCodeId('auth-code-1');

        self::assertNull($this->nonceService->findNonceByAuthCodeId('auth-code-1'));
    }

    public function testRoundTripsNonceByAccessTokenId(): void
    {
        $this->nonceService->storeNonceByAccessTokenId('access-token-1', 'nonce-abc');

        self::assertSame('nonce-abc', $this->nonceService->findNonceByAccessTokenId('access-token-1'));
    }

    public function testReverseLooksUpAccessTokenIdByNonce(): void
    {
        $this->nonceService->storeNonceByAccessTokenId('access-token-1', 'nonce-abc');

        self::assertSame('access-token-1', $this->nonceService->findAccessTokenIdByNonce('nonce-abc'));
    }

    public function testRemoveNonceByAccessTokenIdDeletesBothIndexes(): void
    {
        $this->nonceService->storeNonceByAccessTokenId('access-token-1', 'nonce-abc');

        $this->nonceService->removeNonceByAccessTokenId('access-token-1');

        self::assertNull($this->nonceService->findNonceByAccessTokenId('access-token-1'));
        self::assertNull($this->nonceService->findAccessTokenIdByNonce('nonce-abc'));
    }

    public function testStoresAreIsolatedPerIdentifier(): void
    {
        $this->nonceService->storeNonceByAuthCodeId('auth-code-1', 'nonce-one');
        $this->nonceService->storeNonceByAuthCodeId('auth-code-2', 'nonce-two');

        self::assertSame('nonce-one', $this->nonceService->findNonceByAuthCodeId('auth-code-1'));
        self::assertSame('nonce-two', $this->nonceService->findNonceByAuthCodeId('auth-code-2'));
    }
}