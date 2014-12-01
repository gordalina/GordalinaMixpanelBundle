<?php

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
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;

class AuthenticationListener
{
    /**
     * @var SecurityContextInterface
     */
    private $securityContext;

    /**
     * @param SecurityContextInterface $securityContext
     * @param Authentication $authentication
     */
    public function __construct(SecurityContextInterface $securityContext, Authentication $authentication)
    {
        $this->securityContext = $securityContext;
        $this->authentication = $authentication;
    }

    /**
     * @param  AuthenticationEvent $e
     * @return null
     */
    public function onAuthenticationSuccess(AuthenticationEvent $e)
    {
        $this->authentication->onAuthenticationSuccess($e->getToken());
    }

    /**
     * @param  AuthenticationFailureEvent $e
     * @return null
     */
    public function onAuthenticationFailure(AuthenticationFailureEvent $e)
    {
        $this->authentication->onAuthenticationFailure();
    }

    /**
     * @param  InteractiveLoginEvent $e
     * @return null
     */
    public function onInteractiveLogin(InteractiveLoginEvent $e)
    {
        $this->authentication->onAuthenticationSuccess($e->getAuthenticationToken());
    }

    /**
     * @param  GetResponseEvent $e
     * @return null
     */
    public function onKernelRequest(GetResponseEvent $e)
    {
        $token = $this->securityContext->getToken();

        if ($token instanceof TokenInterface) {
            $this->authentication->onAuthenticationSuccess($token);
        }
    }

    /**
     * @param  SwitchUserEvent $e
     * @return null
     */
    public function onSwitchUser(SwitchUserEvent $e)
    {
        $this->authentication->onAuthenticationFailure();

        $token = $this->securityContext->getToken();

        if ($token instanceof TokenInterface) {
            $this->authentication->onAuthenticationSuccess($token);
        }
    }
}
