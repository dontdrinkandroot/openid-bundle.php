<?php

namespace Dontdrinkandroot\OpenIdBundle\Tests\Unit\Event\Listener;

use Dontdrinkandroot\OpenIdBundle\Event\Listener\UserResolveListener;
use Dontdrinkandroot\OpenIdBundle\Tests\Fixture\TestPasswordUser;
use Dontdrinkandroot\OpenIdBundle\Tests\Fixture\TestUser;
use League\Bundle\OAuth2ServerBundle\Event\UserResolveEvent;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\ValueObject\Grant;
use Override;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class UserResolveListenerTest extends TestCase
{
    /** @var UserProviderInterface<UserInterface>&MockObject */
    private UserProviderInterface&MockObject $userProvider;
    private UserPasswordHasherInterface&MockObject $passwordHasher;
    private UserResolveListener $listener;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->userProvider = $this->createMock(UserProviderInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->listener = new UserResolveListener($this->userProvider, $this->passwordHasher);
    }

    public function testSetsUserWhenPasswordIsValid(): void
    {
        $user = new TestPasswordUser('user-1', 'hash-1');
        $this->arrangeUserResolved($user);
        $this->arrangePasswordValid($user, 'secret');

        $event = $this->resolveUser('user-1', 'secret');

        self::assertSame($user, $event->getUser());
    }

    public function testLeavesUserUnsetWhenPasswordIsInvalid(): void
    {
        $user = new TestPasswordUser('user-1', 'hash-1');
        $this->arrangeUserResolved($user);
        $this->arrangePasswordInvalid($user, 'wrong');

        $event = $this->resolveUser('user-1', 'wrong');

        self::assertNull($event->getUser());
    }

    public function testLeavesUserUnsetWhenLoadedUserIsNotPasswordAuthenticated(): void
    {
        $user = new TestUser('user-1');
        $this->arrangeUserResolved($user);
        $this->passwordHasher->expects($this->never())->method('isPasswordValid');

        $event = $this->resolveUser('user-1', 'secret');

        self::assertNull($event->getUser());
    }

    public function testPropagatesUserProviderException(): void
    {
        $this->userProvider->expects($this->once())->method('loadUserByIdentifier')->willThrowException(new UserNotFoundException());
        $this->passwordHasher->expects($this->never())->method('isPasswordValid');

        $this->expectException(UserNotFoundException::class);

        $this->resolveUser('user-1', 'secret');
    }

    private function arrangeUserResolved(UserInterface $user): void
    {
        $this->userProvider
            ->expects($this->once())
            ->method('loadUserByIdentifier')
            ->with('user-1')
            ->willReturn($user);
    }

    private function arrangePasswordValid(TestPasswordUser $user, string $password): void
    {
        $this->passwordHasher->expects($this->once())->method('isPasswordValid')->with($user, $password)->willReturn(true);
    }

    private function arrangePasswordInvalid(TestPasswordUser $user, string $password): void
    {
        $this->passwordHasher->expects($this->once())->method('isPasswordValid')->with($user, $password)->willReturn(false);
    }

    private function resolveUser(string $username, string $password): UserResolveEvent
    {
        $event = new UserResolveEvent($username, $password, new Grant('password'), $this->createClient());

        $this->listener->onUserResolve($event);

        return $event;
    }

    private function createClient(): Client
    {
        return new Client('client-name', 'client-identifier', null);
    }
}
