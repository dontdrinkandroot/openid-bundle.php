<?php

namespace Dontdrinkandroot\OpenIdBundle\Tests\Unit\Controller;

use Dontdrinkandroot\OpenIdBundle\Controller\UserInfoAction;
use Dontdrinkandroot\OpenIdBundle\Service\ScopeProvider\ScopeProviderInterface;
use Dontdrinkandroot\OpenIdBundle\Tests\Fixture\TestUser;
use League\Bundle\OAuth2ServerBundle\Security\Authentication\Token\OAuth2Token;
use LogicException;
use Override;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;

final class UserInfoActionTest extends TestCase
{
    private const string ROLE_PREFIX = 'ROLE_OAUTH2_';
    private const string CLIENT_ID = 'client-1';
    private const string ACCESS_TOKEN_ID = 'access-token-1';

    private TestUser $user;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = new TestUser('user-1');
    }

    public function testReturnsMergedClaimsFromMatchingProviders(): void
    {
        $action = $this->createAction(
            $this->createToken(['openid', 'profile']),
            [new SubClaimProvider(), new NameClaimProvider()]
        );

        $response = $action(new Request());

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(
            ['sub' => 'user-1', 'name' => 'Alice'],
            json_decode((string)$response->getContent(), true, 512, JSON_THROW_ON_ERROR)
        );
    }

    public function testSkipsProvidersReturningFalse(): void
    {
        $action = $this->createAction(
            $this->createToken(['profile']),
            [new SubClaimProvider()]
        );

        $response = $action(new Request());

        self::assertSame([], json_decode((string)$response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testLaterScopesOverrideEarlierClaimKeys(): void
    {
        $action = $this->createAction(
            $this->createToken(['openid', 'profile']),
            [new NameClaimProvider(), new OverridingNameClaimProvider()]
        );

        $response = $action(new Request());

        self::assertSame(
            ['name' => 'Bob'],
            json_decode((string)$response->getContent(), true, 512, JSON_THROW_ON_ERROR)
        );
    }

    public function testThrowsAccessDeniedWhenTokenIsMissing(): void
    {
        $action = new UserInfoAction(new FixedTokenStorage(null), [new SubClaimProvider()]);

        $this->expectException(AccessDeniedException::class);

        $action(new Request());
    }

    public function testThrowsAccessDeniedWhenTokenIsNotOAuth2Token(): void
    {
        $action = new UserInfoAction(new FixedTokenStorage(new NullToken()), [new SubClaimProvider()]);

        $this->expectException(AccessDeniedException::class);

        $action(new Request());
    }

    private function createTokenStorage(TokenInterface $token): TokenStorageInterface
    {
        return new FixedTokenStorage($token);
    }

    /**
     * @param list<non-empty-string> $scopes
     */
    private function createToken(array $scopes): OAuth2Token
    {
        return new OAuth2Token($this->user, self::ACCESS_TOKEN_ID, self::CLIENT_ID, $scopes, self::ROLE_PREFIX);
    }

    /**
     * @param ScopeProviderInterface<UserInterface>[] $scopeProviders
     */
    private function createAction(TokenInterface $token, array $scopeProviders): UserInfoAction
    {
        return new UserInfoAction($this->createTokenStorage($token), $scopeProviders);
    }
}

/**
 * @implements ScopeProviderInterface<UserInterface>
 */
final class SubClaimProvider implements ScopeProviderInterface
{
    #[Override]
    public function provideInfoForScope(UserInterface $user, string $scope): array|false
    {
        if ('openid' !== $scope) {
            return false;
        }

        return ['sub' => $user->getUserIdentifier()];
    }
}

/**
 * @implements ScopeProviderInterface<UserInterface>
 */
final class NameClaimProvider implements ScopeProviderInterface
{
    #[Override]
    public function provideInfoForScope(UserInterface $user, string $scope): array|false
    {
        if ('profile' !== $scope) {
            return false;
        }

        return ['name' => 'Alice'];
    }
}

/**
 * @implements ScopeProviderInterface<UserInterface>
 */

/**
 * @implements ScopeProviderInterface<UserInterface>
 */
final class OverridingNameClaimProvider implements ScopeProviderInterface
{
    #[Override]
    public function provideInfoForScope(UserInterface $user, string $scope): array|false
    {
        if ('profile' !== $scope) {
            return false;
        }

        return ['name' => 'Bob'];
    }
}

final class FixedTokenStorage implements TokenStorageInterface
{
    public function __construct(
        private readonly ?TokenInterface $token = null
    ) {
    }

    #[Override]
    public function getToken(): ?TokenInterface
    {
        return $this->token;
    }

    #[Override]
    public function setToken(?TokenInterface $token): void
    {
        throw new LogicException('Not expected to be called in this test');
    }
}
