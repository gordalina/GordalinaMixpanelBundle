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

use Gordalina\MixpanelBundle\MixPanel\ManagerRegistry;
use Gordalina\MixpanelBundle\MixPanel\Security\Authentication;
use Gordalina\MixpanelBundle\MixPanel\Security\UserData;
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
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var UserData
     */
    private $userData;

    /**
     * @var bool
     */
    private $autoUpdateUser;

    /**
     * @var bool
     */
    private $sendDataToMixpanel;

    public function __construct(TokenStorageInterface $tokenStorage, Authentication $authentication, ManagerRegistry $registry, UserData $userData, bool $autoUpdateUser, bool $sendDataToMixpanel)
    {
        $this->tokenStorage       = $tokenStorage;
        $this->authentication     = $authentication;
        $this->registry           = $registry;
        $this->userData           = $userData;
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

            $userId     = $this->userData->getId();
            $properties = $this->userData->getProperties();
            unset($properties['id']);
            /** @var \Mixpanel $project */
            foreach ($this->registry->getProjects() as $project) {
                $project->people->set($userId, $properties, $e->getRequest()->getClientIp());
            }
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
