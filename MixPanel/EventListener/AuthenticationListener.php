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

namespace Gordalina\MixpanelBundle\MixPanel\EventListener;

use Gordalina\MixpanelBundle\Annotation\UpdateUser;
use Gordalina\MixpanelBundle\MixPanel\Event\MixPanelEvent;
use Gordalina\MixpanelBundle\MixPanel\Security\Authentication;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
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

    public function __construct(TokenStorageInterface $tokenStorage, Authentication $authentication, EventDispatcherInterface $eventDispatcher , bool $autoUpdateUser, bool $sendDataToMixpanel)
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

    public function onKernelRequest(RequestEvent $e)
    {
        $token = $this->tokenStorage->getToken();

        if ($e->isMasterRequest() && $token instanceof TokenInterface) {
            $this->authentication->onAuthenticationSuccess($token);

            if (!$this->autoUpdateUser || !$this->sendDataToMixpanel) {
                return;
            }

            $this->eventDispatcher->dispatch(new MixPanelEvent(new UpdateUser(), $e->getRequest()));
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
