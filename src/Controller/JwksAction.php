<?php

namespace Dontdrinkandroot\OpenIdBundle\Controller;

use Dontdrinkandroot\Common\Asserted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class JwksAction extends AbstractController
{
    private string $publicKey;

    public function __construct(string $publicKeyPath)
    {
        $this->publicKey = Asserted::notFalse(file_get_contents($publicKeyPath), 'Could not read public key');
    }

    public function __invoke(): Response
    {
        $details = openssl_pkey_get_details(openssl_pkey_get_public($this->publicKey));
        if (false === $details || !isset($details['rsa'])) {
            return new JsonResponse(['error' => 'Invalid public key format'], 500);
        }

        $n = $this->base64UrlEncode($details['rsa']['n']);
        $e = $this->base64UrlEncode($details['rsa']['e']);
        $kid = substr(sha1($details['key']), 0, 16);

        return new JsonResponse([
            'keys' => [
                [
                    'kty' => 'RSA',
                    'alg' => 'RS256',
                    'use' => 'sig',
                    'n' => $n,
                    'e' => $e,
                    'kid' => $kid,
                ],
            ],
        ]);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
