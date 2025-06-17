<?php

namespace Dontdrinkandroot\OpenIdBundle\Service\Nonce;

interface NonceServiceInterface
{
    public function storeNonceByAuthCodeId(string $authCodeId, string $nonce): void;

    public function findNonceByAuthCodeId(string $authCodeId): ?string;

    public function removeNonceByAuthCodeId(string $authCodeId): void;

    public function storeNonceByAccessTokenId(string $accessTokenId, string $nonce): void;

    public function findNonceByAccessTokenId(string $accessTokenId): ?string;

    public function removeNonceByAccessTokenId(string $accessTokenId): void;

    public function findAccessTokenIdByNonce(string $nonce): ?string;
}
