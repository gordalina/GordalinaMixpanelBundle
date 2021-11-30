<?php

declare(strict_types=1);

/*
 * This file is part of the mixpanel bundle.
 *
 * (c) Samuel Gordalina <https://github.com/gordalina/mixpanel-bundle>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gordalina\MixpanelBundle\Mixpanel\EventListener;

use Gordalina\MixpanelBundle\Annotation\UpdateUser;
use Gordalina\MixpanelBundle\Mixpanel\Event\MixpanelEvent;
use Gordalina\MixpanelBundle\Mixpanel\Security\Authentication;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;

class AuthenticationListener
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var Authentication
     */
    private $authentication;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var bool
     */
    private $autoUpdateUser;

    /**
     * @var bool
     */
    private $sendDataToMixpanel;

    public function __construct(TokenStorageInterface $tokenStorage, Authentication $authentication, EventDispatcherInterface $eventDispatcher, bool $autoUpdateUser, bool $sendDataToMixpanel)
    {
        $this->tokenStorage       = $tokenStorage;
        $this->authentication     = $authentication;
        $this->eventDispatcher    = $eventDispatcher;
        $this->autoUpdateUser     = $autoUpdateUser;
        $this->sendDataToMixpanel = $sendDataToMixpanel;
    }

    public function onAuthenticationSuccess(AuthenticationEvent $e)
    {
        $this->authentication->onAuthenticationSuccess($e->getAuthenticationToken());
    }

    public function onAuthenticationFailure(AuthenticationFailureEvent $e)
    {
        $this->authentication->onAuthenticationFailure();
    }

    public function onInteractiveLogin(InteractiveLoginEvent $e)
    {
        $this->authentication->onAuthenticationSuccess($e->getAuthenticationToken());
    }

    public function onSuccessfulLogin(LoginSuccessEvent $event): void
    {
        $this->authentication->onAuthenticationSuccess($event->getAuthenticatedToken());
    }

    public function onKernelRequest(RequestEvent $e)
    {
        $token = $this->tokenStorage->getToken();

        if ($e->isMainRequest() && $token instanceof TokenInterface) {
            $this->authentication->onAuthenticationSuccess($token);

            if (!$this->autoUpdateUser || !$this->sendDataToMixpanel) {
                return;
            }

            $this->eventDispatcher->dispatch(new MixpanelEvent(new UpdateUser(), $e->getRequest()));
        }
    }

    public function onSwitchUser(SwitchUserEvent $e)
    {
        $this->authentication->onAuthenticationFailure();

        $token = $this->tokenStorage->getToken();

        if ($token instanceof TokenInterface) {
            $this->authentication->onAuthenticationSuccess($token);
        }
    }
}
