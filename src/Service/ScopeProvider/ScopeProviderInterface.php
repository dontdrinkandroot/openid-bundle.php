<?php

namespace Dontdrinkandroot\OpenIdBundle\Service\ScopeProvider;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @template T of UserInterface
 */
interface ScopeProviderInterface
{
    /**
     * @param T $user
     * @return array<string,mixed>|false Contributes to the user info or returns false if scope does not match.
     */
    public function provideInfoForScope(UserInterface $user, string $scope): array|false;
}
