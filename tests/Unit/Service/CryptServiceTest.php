<?php

namespace Dontdrinkandroot\OpenIdBundle\Tests\Unit\Service;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Dontdrinkandroot\OpenIdBundle\Service\CryptService;
use PHPUnit\Framework\TestCase;

final class CryptServiceTest extends TestCase
{
    public function testRoundTripsEncryptedJsonPayload(): void
    {
        $key = Key::createNewRandomKey();
        $cryptService = new CryptService($key);
        $payload = ['auth_code_id' => 'auth-code-1', 'client_id' => 'client-a'];
        $code = Crypto::encrypt((string)json_encode($payload), $key);

        $decrypted = $cryptService->decryptCode($code);

        self::assertSame($payload, $decrypted);
    }

    public function testReturnsNullWhenPlaintextIsNotJson(): void
    {
        $key = Key::createNewRandomKey();
        $cryptService = new CryptService($key);
        $code = Crypto::encrypt('not-json', $key);

        self::assertNull($cryptService->decryptCode($code));
    }
}