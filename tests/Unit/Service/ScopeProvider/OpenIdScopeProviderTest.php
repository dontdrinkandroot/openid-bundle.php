<?php

namespace Dontdrinkandroot\OpenIdBundle\Tests\Unit\Service\ScopeProvider;

use Dontdrinkandroot\OpenIdBundle\Service\ScopeProvider\OpenIdScopeProvider;
use Dontdrinkandroot\OpenIdBundle\Tests\Fixture\TestUser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OpenIdScopeProviderTest extends TestCase
{
    public function testProvidesSubClaimForOpenIdScope(): void
    {
        $user = new TestUser('user-123');

        $claims = (new OpenIdScopeProvider())->provideInfoForScope($user, 'openid');

        self::assertSame(['sub' => 'user-123'], $claims);
    }

    /**
     * @return iterable<non-empty-string, list{non-empty-string}>
     */
    public static function provideForeignScopes(): iterable
    {
        return [
            'profile' => ['profile'],
            'email' => ['email'],
        ];
    }

    #[DataProvider('provideForeignScopes')]
    public function testIgnoresOtherScopes(string $scope): void
    {
        $user = new TestUser('user-123');

        $claims = (new OpenIdScopeProvider())->provideInfoForScope($user, $scope);

        self::assertFalse($claims);
    }
}
