<?php

declare(strict_types=1);

namespace spec\Gordalina\MixpanelBundle\Mixpanel\Security;

use Gordalina\MixpanelBundle\Mixpanel\ManagerRegistry;
use PhpSpec\ObjectBehavior;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserDataSpec extends ObjectBehavior
{
    public function let(TokenStorageInterface $tokenStorage, ManagerRegistry $registry)
    {
        $this->beConstructedWith($tokenStorage, $registry, true);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Gordalina\MixpanelBundle\Mixpanel\Security\UserData');
    }

    public function it_should_return_empty_properties_when_user_is_anonymous(
        TokenStorageInterface $tokenStorage,
        TokenInterface $token
    ) {
        $token->getUser()->willReturn(null);

        $tokenStorage->getToken()->willReturn($token);

        $this->getProperties()->shouldReturn([]);
    }

    public function it_should_return_properties_for_proxies(
        TokenStorageInterface $tokenStorage,
        TokenInterface $token,
        ManagerRegistry $registry
    ) {
        $registry->getUsers()->willReturn([TestUser::class => [
            'id' => 'id',
        ]]);

        $user = new TestUserProxy();

        $token->getUser()->willReturn($user);

        $tokenStorage->getToken()->willReturn($token);

        $this->getProperties()->shouldReturn(['id' => '1']);
    }

    public function it_should_format_date_time_objects(
        TokenStorageInterface $tokenStorage,
        TokenInterface $token,
        ManagerRegistry $registry
    ) {
        $registry->getUsers()->willReturn([TestUser::class => [
            'id' => 'id',
            'extra_data' => [
                ['key' => 'created_at', 'value' => 'createdAt']
            ],
        ]]);

        $user = new TestUser();

        $token->getUser()->willReturn($user);

        $tokenStorage->getToken()->willReturn($token);

        $this->getProperties()->shouldReturn(['id' => '1', 'created_at' => '2020-01-01T00:00:00+00:00']);
    }
}

class TestUser implements UserInterface
{
    public function getRoles(): array
    {
        return [];
    }

    public function eraseCredentials(): void
    {
        return;
    }

    public function getUserIdentifier(): string
    {
        return '1';
    }

    public function getId(): string
    {
        return $this->getUserIdentifier();
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2020-01-01');
    }
}

class TestUserProxy extends TestUser
{
}
