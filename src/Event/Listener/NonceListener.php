<?php

namespace Dontdrinkandroot\OpenIdBundle\Event\Listener;

use Dontdrinkandroot\Common\Asserted;
use Dontdrinkandroot\OpenIdBundle\Service\CryptService;
use Dontdrinkandroot\OpenIdBundle\Service\Nonce\NonceServiceInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class NonceListener
{
    public function __construct(
        private readonly NonceServiceInterface $nonceService,
        private readonly CryptService $cryptService
    ) {
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if (
            $this->isAuthorizeRequest($request)
            && null !== ($nonce = $this->getNonce($request))
            && null !== ($code = $this->getCode($response))
            && null !== ($codeContent = $this->cryptService->decryptCode($code))
            && null !== ($authCodeId = $codeContent['auth_code_id'] ?? null)
        ) {
            $this->nonceService->storeNonceByAuthCodeId($authCodeId, $nonce);
        }
    }

    private function isAuthorizeRequest(Request $request): bool
    {
        return 'oauth2_authorize' === $request->attributes->get('_route');
    }

    private function isTokenRequest(Request $request): bool
    {
        return 'oauth2_token' === $request->attributes->get('_route');
    }

    private function getNonce(Request $request): ?string
    {
        return Asserted::stringOrNull($request->query->get('nonce'));
    }

    private function getCode(Response $response): ?string
    {
        if (
            null === ($location = $response->headers->get('Location'))
            || null === ($queryString = Asserted::stringOrNull(parse_url($location, PHP_URL_QUERY)))
        ) {
            return null;
        }

        parse_str($queryString, $params);

        return $params['code'] ?? null;
    }
}
