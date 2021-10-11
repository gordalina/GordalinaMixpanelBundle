<?php

declare(strict_types=1);

namespace spec\Gordalina\MixpanelBundle\Mixpanel\EventListener;

use Gordalina\MixpanelBundle\Mixpanel\Security\Authentication;
use PhpSpec\ObjectBehavior;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class AuthenticationListenerSpec extends ObjectBehavior
{
    public function let(TokenStorageInterface $tokenStorage, Authentication $authentication, EventDispatcherInterface $eventSubscriber)
    {
        $this->beConstructedWith($tokenStorage, $authentication, $eventSubscriber, true, true);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Gordalina\MixpanelBundle\Mixpanel\EventListener\AuthenticationListener');
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

    public function it_should_pass_authenticated_token_to_authentication_service(
        Authentication $authentication,
        LoginSuccessEvent $event,
        TokenInterface $token
    ) {
        $event->getAuthenticatedToken()->willReturn($token);

        $authentication->onAuthenticationSuccess($token)->shouldBeCalled();

        $this->onSuccessfulLogin($event);
    }
}
