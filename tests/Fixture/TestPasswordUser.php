<?php

namespace Dontdrinkandroot\OpenIdBundle\Tests\Fixture;

use Override;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class TestPasswordUser extends TestUser implements PasswordAuthenticatedUserInterface
{
    public function __construct(
        string $identifier,
        private readonly string $password
    ) {
        parent::__construct($identifier);
    }

    #[Override]
    public function getPassword(): ?string
    {
        return $this->password;
    }
}