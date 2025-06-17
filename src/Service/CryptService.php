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

    public function decryptCode(string $code): ?array
    {
        return json_decode($this->decrypt($code), true);
    }
}
