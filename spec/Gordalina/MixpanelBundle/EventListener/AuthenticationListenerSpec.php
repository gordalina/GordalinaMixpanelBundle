<?php

declare(strict_types=1);

namespace spec\Gordalina\MixpanelBundle\EventListener;

use Gordalina\MixpanelBundle\Security\Authentication;
use PhpSpec\ObjectBehavior;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;

class AuthenticationListenerSpec extends ObjectBehavior
{
    public function let(TokenStorageInterface $tokenStorage, Authentication $authentication)
    {
        $this->beConstructedWith($tokenStorage, $authentication);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Gordalina\MixpanelBundle\EventListener\AuthenticationListener');
    }

    public function it_should_pass_authentication_token_to_authentication_service(
        Authentication $authentication,
        AuthenticationEvent $event,
        TokenInterface $token
    ) {
        $event->getAuthenticationToken()->willReturn($token);

        $authentication->onAuthenticationSuccess($token)->shouldBeCalled();

        $this->onAuthenticationSuccess($event);
    }
}
