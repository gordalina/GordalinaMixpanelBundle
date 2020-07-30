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

namespace Gordalina\MixpanelBundle\EventListener;

use Gordalina\MixpanelBundle\Security\Authentication;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
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

    public function __construct(TokenStorageInterface $tokenStorage, Authentication $authentication)
    {
        $this->tokenStorage   = $tokenStorage;
        $this->authentication = $authentication;
    }

    /**
     * @return null
     */
    public function onAuthenticationSuccess(AuthenticationEvent $e)
    {
        $this->authentication->onAuthenticationSuccess($e->getAuthenticationToken());
    }

    /**
     * @return null
     */
    public function onAuthenticationFailure(AuthenticationFailureEvent $e)
    {
        $this->authentication->onAuthenticationFailure();
    }

    /**
     * @return null
     */
    public function onInteractiveLogin(InteractiveLoginEvent $e)
    {
        $this->authentication->onAuthenticationSuccess($e->getAuthenticationToken());
    }

    /**
     * @return null
     */
    public function onKernelRequest(GetResponseEvent $e)
    {
        $token = $this->tokenStorage->getToken();

        if ($token instanceof TokenInterface) {
            $this->authentication->onAuthenticationSuccess($token);
        }
    }

    /**
     * @return null
     */
    public function onSwitchUser(SwitchUserEvent $e)
    {
        $this->authentication->onAuthenticationFailure();

        $token = $this->tokenStorage->getToken();

        if ($token instanceof TokenInterface) {
            $this->authentication->onAuthenticationSuccess($token);
        }
    }
}
