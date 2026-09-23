<?php

namespace Dontdrinkandroot\OpenIdBundle\Tests\Fixture;

use Override;
use Symfony\Component\Security\Core\User\UserInterface;

class TestUser implements UserInterface
{
    /**
     * @param non-empty-string $identifier
     */
    public function __construct(
        private readonly string $identifier
    ) {
    }

    #[Override]
    public function getRoles(): array
    {
        return [];
    }

    #[Override]
    public function eraseCredentials(): void
    {
    }

    /**
     * @return non-empty-string
     */
    #[Override]
    public function getUserIdentifier(): string
    {
        return $this->identifier;
    }
}