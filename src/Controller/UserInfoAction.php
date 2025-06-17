<?php

namespace Dontdrinkandroot\OpenIdBundle\Controller;

use Dontdrinkandroot\OpenIdBundle\Service\ScopeProvider\ScopeProviderInterface;
use League\Bundle\OAuth2ServerBundle\Security\Authentication\Token\OAuth2Token;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserInfoAction extends AbstractController
{
    /**
     * @param iterable<ScopeProviderInterface<UserInterface>> $scopeProviders
     */
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly iterable $scopeProviders
    ) {
    }

    public function __invoke(Request $request): Response
    {
        if (
            !(($token = $this->tokenStorage->getToken()) instanceof OAuth2Token)
            || null === ($user = $token->getUser())
        ) {
            throw $this->createAccessDeniedException('Invalid context');
        }

        $userInfo = [];
        $scopes = $token->getScopes();
        foreach ($scopes as $scope) {
            foreach ($this->scopeProviders as $scopeProvider) {
                $scopeProviderInfo = $scopeProvider->provideInfoForScope($user, $scope);
                if (false !== $scopeProviderInfo) {
                    $userInfo = array_merge($userInfo, $scopeProviderInfo);
                }
            }
        }

        return new JsonResponse($userInfo);
    }
}
