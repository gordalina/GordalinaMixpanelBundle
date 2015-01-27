<?php

namespace spec\Gordalina\MixpanelBundle\Security;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class UserDataSpec extends ObjectBehavior
{
    /**
     * @param Symfony\Component\Security\Core\SecurityContextInterface $securityContext
     * @param Gordalina\MixpanelBundle\ManagerRegistry $registry
     */
    function let($securityContext, $registry)
    {
        $this->beConstructedWith($securityContext, $registry);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Gordalina\MixpanelBundle\Security\UserData');
    }

    /**
     * @param Symfony\Component\Security\Core\SecurityContextInterface $securityContext
     * @param Symfony\Component\Security\Core\Authentication\Token\AnonymousToken $token
     */
    function it_should_return_empty_properties_when_user_is_anonymous($securityContext, $token)
    {
        $securityContext->getToken()->willReturn($token);

        $token->getUser()->willReturn('anon.');

        $this->getProperties()->shouldReturn(array());
    }
}
