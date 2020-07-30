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

namespace Gordalina\MixpanelBundle\Security;

use Gordalina\MixpanelBundle\ManagerRegistry;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class Authentication
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var UserData
     */
    private $userData;

    public function __construct(ManagerRegistry $registry, UserData $userData)
    {
        $this->registry = $registry;
        $this->userData = $userData;
    }

    /**
     * @return bool
     */
    public function onAuthenticationSuccess(TokenInterface $token)
    {
        if (null === ($user = $token->getUser())) {
            return null;
        }

        $id = $this->userData->getId($user);

        foreach ($this->registry->getProjects() as $project) {
            $project->identify($id);
        }

        return true;
    }

    /**
     * @return null
     */
    public function onAuthenticationFailure()
    {
        $this->registry->flush();

        foreach ($this->registry->getProjects() as $project) {
            // clear identity
            $project->identify(null);
            $project->unregister('distinct_id');
        }
    }
}
