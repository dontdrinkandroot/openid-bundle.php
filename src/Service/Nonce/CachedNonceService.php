<?php

namespace Dontdrinkandroot\OpenIdBundle\Service\Nonce;

use Dontdrinkandroot\OpenIdBundle\Service\CryptService;
use Override;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Contracts\Cache\CacheInterface;

class CachedNonceService implements NonceServiceInterface
{
    private const int AUTH_CODE_TTL = 600;
    private const string CACHE_KEY_PREFIX_AUTH_CODE = 'nonce_auth_code_';
    private const string CACHE_KEY_PREFIX_ACCESS_TOKEN = 'nonce_access_token_';
    private const string CACHE_KEY_PREFIX_NONCE = 'nonce_';

    public function __construct(
        private readonly AdapterInterface $cacheAdapter
    ) {
    }

    #[Override]
    public function storeNonceByAuthCodeId(string $authCodeId, string $nonce): void
    {
        $cacheKey = $this->generateAuthCodeCacheKey($authCodeId);
        $cacheItem = $this->cacheAdapter->getItem($cacheKey);
        $cacheItem->set($nonce);
        $cacheItem->expiresAfter(self::AUTH_CODE_TTL);
        $this->cacheAdapter->save($cacheItem);
    }

    #[Override]
    public function findNonceByAuthCodeId(string $authCodeId): ?string
    {
        $cacheKey = $this->generateAuthCodeCacheKey($authCodeId);
        $cacheItem = $this->cacheAdapter->getItem($cacheKey);
        if (!$cacheItem->isHit()) {
            return null;
        }

        return $cacheItem->get();
    }

    #[Override]
    public function removeNonceByAuthCodeId(string $authCodeId): void
    {
        $cacheKey = $this->generateAuthCodeCacheKey($authCodeId);
        $this->cacheAdapter->deleteItem($cacheKey);
    }

    #[Override]
    public function storeNonceByAccessTokenId(string $accessTokenId, string $nonce): void
    {
        $cacheKey = $this->generateAccessTokenCacheKey($accessTokenId);
        $cacheItem = $this->cacheAdapter->getItem($cacheKey);
        $cacheItem->set($nonce);
        $this->cacheAdapter->save($cacheItem);

        $this->storeAccessTokenIdByNonce($nonce, $accessTokenId);
    }

    private function storeAccessTokenIdByNonce(string $nonce, string $accessTokenId): void {
        $cacheKey = $this->generateNonceCacheKey($nonce);
        $cacheItem = $this->cacheAdapter->getItem($cacheKey);
        $cacheItem->set($accessTokenId);
    }

    #[Override]
    public function findNonceByAccessTokenId(string $accessTokenId): ?string
    {
        $cacheKey = $this->generateAccessTokenCacheKey($accessTokenId);
        $cacheItem = $this->cacheAdapter->getItem($cacheKey);
        if (!$cacheItem->isHit()) {
            return null;
        }

        return $cacheItem->get();
    }

    #[Override]
    public function removeNonceByAccessTokenId(string $accessTokenId): void
    {
        $cacheKey = $this->generateAccessTokenCacheKey($accessTokenId);
        $this->cacheAdapter->deleteItem($cacheKey);

        $nonce = $this->findNonceByAccessTokenId($accessTokenId);
        if (null !== $nonce) {
            $this->removeAccessTokenIdByNonce($nonce);
        }
    }

    private function removeAccessTokenIdByNonce(string $nonce): void {
        $cacheKey = $this->generateNonceCacheKey($nonce);
        $this->cacheAdapter->deleteItem($cacheKey);
    }

    #[Override]
    public function findAccessTokenIdByNonce(string $nonce): ?string
    {
        $cacheKey = $this->generateNonceCacheKey($nonce);
        $cacheItem = $this->cacheAdapter->getItem($cacheKey);
        if (!$cacheItem->isHit()) {
            return null;
        }

        return $cacheItem->get();
    }

    private function generateAuthCodeCacheKey(string $authCodeId): string
    {
        return self::CACHE_KEY_PREFIX_AUTH_CODE . $authCodeId;
    }

    private function generateAccessTokenCacheKey(string $accessTokenId): string
    {
        return self::CACHE_KEY_PREFIX_ACCESS_TOKEN . $accessTokenId;
    }

    private function generateNonceCacheKey(string $nonce): string {
        return self::CACHE_KEY_PREFIX_NONCE . $nonce;
    }
}
