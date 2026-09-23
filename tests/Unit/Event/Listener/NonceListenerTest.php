<?php

namespace Dontdrinkandroot\OpenIdBundle\Tests\Unit\Event\Listener;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Dontdrinkandroot\OpenIdBundle\Event\Listener\NonceListener;
use Dontdrinkandroot\OpenIdBundle\Service\CryptService;
use Dontdrinkandroot\OpenIdBundle\Service\Nonce\CachedNonceService;
use LogicException;
use Override;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class NonceListenerTest extends TestCase
{
    private const string REDIRECT_URI = 'https://client.example/callback';

    private Key $encryptionKey;
    private CachedNonceService $nonceService;
    private NonceListener $listener;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->encryptionKey = Key::createNewRandomKey();
        $this->nonceService = new CachedNonceService(new ArrayAdapter());
        $this->listener = new NonceListener($this->nonceService, new CryptService($this->encryptionKey));
    }

    public function testStoresNonceByAuthCodeIdOnAuthorizeRedirect(): void
    {
        $event = $this->createResponseEvent(
            $this->createAuthorizeRequest('nonce-abc'),
            $this->createRedirectResponse($this->encryptCode('auth-code-1'))
        );

        $this->listener->onKernelResponse($event);

        self::assertSame('nonce-abc', $this->nonceService->findNonceByAuthCodeId('auth-code-1'));
    }

    public function testStoresNothingWhenRequestHasNoNonce(): void
    {
        $event = $this->createResponseEvent(
            $this->createAuthorizeRequest(null),
            $this->createRedirectResponse($this->encryptCode('auth-code-1'))
        );

        $this->listener->onKernelResponse($event);

        self::assertNull($this->nonceService->findNonceByAuthCodeId('auth-code-1'));
    }

    public function testStoresNothingOnNonAuthorizeRoute(): void
    {
        $request = $this->createAuthorizeRequest('nonce-abc');
        $request->attributes->set('_route', 'other_route');
        $event = $this->createResponseEvent(
            $request,
            $this->createRedirectResponse($this->encryptCode('auth-code-1'))
        );

        $this->listener->onKernelResponse($event);

        self::assertNull($this->nonceService->findNonceByAuthCodeId('auth-code-1'));
    }

    public function testStoresNothingWhenRedirectHasNoCode(): void
    {
        $event = $this->createResponseEvent(
            $this->createAuthorizeRequest('nonce-abc'),
            $this->createRedirectResponse(null)
        );

        $this->listener->onKernelResponse($event);

        self::assertNull($this->nonceService->findNonceByAuthCodeId('auth-code-1'));
    }

    public function testDecryptFailurePropagates(): void
    {
        $event = $this->createResponseEvent(
            $this->createAuthorizeRequest('nonce-abc'),
            $this->createRedirectResponse('not-a-valid-ciphertext')
        );

        $this->expectException(\InvalidArgumentException::class);

        $this->listener->onKernelResponse($event);
    }

    private function encryptCode(string $authCodeId): string
    {
        return Crypto::encrypt(
            (string)json_encode(['auth_code_id' => $authCodeId]),
            $this->encryptionKey
        );
    }

    private function createAuthorizeRequest(?string $nonce): Request
    {
        $request = Request::create('https://idp.example/oauth2/authorize');
        $request->attributes->set('_route', 'oauth2_authorize');
        if (null !== $nonce) {
            $request->query->set('nonce', $nonce);
        }

        return $request;
    }

    private function createRedirectResponse(?string $code): Response
    {
        $location = self::REDIRECT_URI;
        if (null !== $code) {
            $location .= '?code=' . urlencode($code);
        }

        return new Response('', 302, ['Location' => $location]);
    }

    private function createResponseEvent(Request $request, Response $response): ResponseEvent
    {
        return new ResponseEvent(new UnusedKernel(), $request, HttpKernelInterface::MAIN_REQUEST, $response);
    }
}

final class UnusedKernel implements HttpKernelInterface
{
    #[Override]
    public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = true): Response
    {
        throw new LogicException('Kernel is not expected to handle requests in this test');
    }
}
