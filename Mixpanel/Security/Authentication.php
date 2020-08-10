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

namespace Gordalina\MixpanelBundle\Mixpanel\Security;

use Gordalina\MixpanelBundle\Mixpanel\ManagerRegistry;
use Gordalina\MixpanelBundle\Mixpanel\Mixpanel\Flusher;
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

    /**
     * @var Flusher
     */
    private $flusher;

    public function __construct(ManagerRegistry $registry, UserData $userData, Flusher $flusher)
    {
        $this->registry = $registry;
        $this->userData = $userData;
        $this->flusher  = $flusher;
    }

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

    public function onAuthenticationFailure()
    {
        $this->flusher->flush();

        foreach ($this->registry->getProjects() as $project) {
            // clear identity
            $project->identify(null);
            $project->unregister('distinct_id');
        }
    }
}
