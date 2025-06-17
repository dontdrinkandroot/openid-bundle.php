<?php

namespace Dontdrinkandroot\OpenIdBundle\Service\ScopeProvider;

use Override;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @template T of UserInterface
 * @implements ScopeProviderInterface<T>
 */
class OpenIdScopeProvider implements ScopeProviderInterface
{
    #[Override]
    public function provideInfoForScope(UserInterface $user, string $scope): array|false
    {
        if ('openid' !== $scope) {
            return false;
        }

        return [
            'sub' => $user->getUserIdentifier(),
        ];
    }
}
