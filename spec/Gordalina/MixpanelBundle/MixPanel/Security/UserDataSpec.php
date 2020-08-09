<?php

declare(strict_types=1);

namespace spec\Gordalina\MixpanelBundle\MixPanel\Security;

use Gordalina\MixpanelBundle\MixPanel\ManagerRegistry;
use PhpSpec\ObjectBehavior;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class UserDataSpec extends ObjectBehavior
{
    public function let(TokenStorageInterface $tokenStorage, ManagerRegistry $registry)
    {
        $this->beConstructedWith($tokenStorage, $registry);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Gordalina\MixpanelBundle\MixPanel\Security\UserData');
    }

    public function it_should_return_empty_properties_when_user_is_anonymous(
        TokenStorageInterface $tokenStorage,
        TokenInterface $token
    ) {
        $tokenStorage->getToken()->willReturn($token);

        $token->getUser()->willReturn('anon.');

        $this->getProperties()->shouldReturn([]);
    }
}