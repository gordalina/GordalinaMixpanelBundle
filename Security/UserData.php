<?php

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
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserData
{
    /**
     * @var array
     */
    private $properties = array();

    /**
     * Lazy loaded
     *
     * @var PropertyAccess
     */
    private $accessor = null;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param ManagerRegistry          $registry
     */
    public function __construct(TokenStorageInterface $tokenStorage, ManagerRegistry $registry)
    {
        $this->tokenStorage = $tokenStorage;
        $this->registry = $registry;
    }

    /**
     * @param  object $instance
     * @return mixed
     */
    public function getId($instance = null)
    {
        $instance = $this->getUser($instance);

        return $this->getProperty($instance, 'id');
    }

    /**
     * @param  object $instance
     * @param  string $property id|first_name|last_name|email|phone
     * @return mixed
     */
    public function getProperty($instance, $property = null)
    {
        if ($property === null) {
            $property = $instance;
            $instance = $this->getUser();
        }

        $properties = $this->getProperties($instance);

        if (is_array($properties) && isset($properties[$property])) {
            return $properties[$property];
        }

        return null;
    }

    /**
     * @param  object $instance
     * @return array
     */
    public function getProperties($instance = null)
    {
        $instance = $this->getUser($instance);
        if (empty($instance) || !is_object($instance)) {
            return array();
        }
        $className = get_class($instance);

        if (isset($this->properties[$className])) {
            return $this->properties[$className];
        }

        foreach ($this->registry->getUsers() as $class => $properties) {
            if ($className === $class) {
                if (!$this->accessor) {
                    $this->accessor = PropertyAccess::createPropertyAccessor();
                }

                $this->properties[$className] = array();


                foreach ($properties as $key => $prop) {
                    $this->properties[$className][$key] = $this->accessor->getValue($instance, $prop);
                }

                return $this->properties[$className];
            }
        }
    }

    /**
     * @param  object|null $instance
     * @return object|null
     */
    public function getUser($instance = null)
    {
        if ($instance !== null) {
            return $instance;
        }

        if (null === ($token = $this->tokenStorage->getToken())) {
            return null;
        }

        if (null === ($user = $token->getUser())) {
            return null;
        }

        return $user;
    }
}
