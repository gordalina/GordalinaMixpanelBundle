<?php

namespace spec\Gordalina\MixpanelBundle\Security;

use Gordalina\MixpanelBundle\ManagerRegistry;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class UserDataSpec extends ObjectBehavior
{
    function let(TokenStorageInterface $tokenStorage, ManagerRegistry $registry)
    {
        $this->beConstructedWith($tokenStorage, $registry);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Gordalina\MixpanelBundle\Security\UserData');
    }

    function it_should_return_empty_properties_when_user_is_anonymous(
        TokenStorageInterface $tokenStorage,
        TokenInterface $token
    ) {
        $tokenStorage->getToken()->willReturn($token);

        $token->getUser()->willReturn('anon.');

        $this->getProperties()->shouldReturn(array());
    }
}
