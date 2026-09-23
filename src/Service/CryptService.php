<?php

namespace Dontdrinkandroot\OpenIdBundle\Service;

use Defuse\Crypto\Key;
use League\OAuth2\Server\CryptTrait;

class CryptService
{
    use CryptTrait;

    public function __construct(Key|string $encryptionKey) {
        $this->encryptionKey = $encryptionKey;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function decryptCode(string $code): ?array
    {
        $decoded = json_decode($this->decrypt($code), true);

        return is_array($decoded) ? $decoded : null;
    }
}
