<?php

namespace Dontdrinkandroot\OpenIdBundle\Controller;

use Dontdrinkandroot\OpenIdBundle\Config\RouteName;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class OpenidConfigurationAction extends AbstractController
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        return new JsonResponse([
            'issuer' => $request->getSchemeAndHttpHost(),
            'authorization_endpoint' => $this->urlGenerator->generate('oauth2_authorize', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'token_endpoint' => $this->urlGenerator->generate('oauth2_token', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'userinfo_endpoint' => $this->urlGenerator->generate(RouteName::USERINFO, [], UrlGeneratorInterface::ABSOLUTE_URL),
            'end_session_endpoint' => $this->urlGenerator->generate(RouteName::LOGOUT, [], UrlGeneratorInterface::ABSOLUTE_URL),
            'jwks_uri' => $this->urlGenerator->generate(RouteName::JWKS, [], UrlGeneratorInterface::ABSOLUTE_URL),
            'subject_types_supported' => ['public'],
            'scopes_supported' => ['openid', 'profile', 'api'],
            // TODO: Try to get it from league config
            'grant_types_supported' => ['authorization_code','refresh_token','password','client_credentials'], // TODO: Try to get it from league config
            'response_types_supported' => ['code'],
            'id_token_signing_alg_values_supported' => ['RS256'],
        ]);
    }
}
