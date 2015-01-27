<?php

namespace spec\Gordalina\MixpanelBundle\EventListener;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class AuthenticationListenerSpec extends ObjectBehavior
{
    /**
     * @param Symfony\Component\Security\Core\SecurityContextInterface $securityContext
     * @param Gordalina\MixpanelBundle\Security\Authentication $authentication
     */
    function let($securityContext, $authentication)
    {
        $this->beConstructedWith($securityContext, $authentication);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Gordalina\MixpanelBundle\EventListener\AuthenticationListener');
    }

    /**
     * @param Gordalina\MixpanelBundle\Security\Authentication $authentication
     * @param Symfony\Component\Security\Core\Event\AuthenticationEvent $event
     * @param Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token
     */
    function it_should_pass_authentication_token_to_authentication_service($authentication, $event, $token)
    {
        $event->getAuthenticationToken()->willReturn($token);

        $authentication->onAuthenticationSuccess($token)->shouldBeCalled();

        $this->onAuthenticationSuccess($event);
    }
}
