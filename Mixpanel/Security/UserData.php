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
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserData
{
    /**
     * @var array
     */
    private $properties = [];

    /**
     * Lazy loaded
     *
     * @var PropertyAccess
     */
    private $accessor;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var bool
     */
    private $displayErrors;

    public function __construct(TokenStorageInterface $tokenStorage, ManagerRegistry $registry, bool $displayErrors)
    {
        $this->tokenStorage  = $tokenStorage;
        $this->registry      = $registry;
        $this->displayErrors = $displayErrors;
    }

    /**
     * @param object $instance
     *
     * @return mixed
     */
    public function getId($instance = null)
    {
        $instance = $this->getUser($instance);

        return $this->getProperty($instance, 'id');
    }

    /**
     * @param object $instance
     * @param string $property id|'$first_name'|'$last_name'|'$email'|'$phone'
     *
     * @return mixed
     */
    public function getProperty($instance, $property = null)
    {
        if (null === $property) {
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
     * @param object $instance
     *
     * @return array
     */
    public function getProperties($instance = null)
    {
        $instance = $this->getUser($instance);
        if (null === $instance) {
            return [];
        }
        $className = get_class($instance);

        if (isset($this->properties[$className])) {
            return $this->properties[$className];
        }

        foreach ($this->registry->getUsers() as $class => $properties) {
            if (!is_a($className, $class, true)) {
                continue;
            }

            if (!$this->accessor) {
                $this->accessor = PropertyAccess::createPropertyAccessor();
            }

            $this->properties[$className] = [];
            foreach ($properties as $key => $prop) {
                if ('extra_data' !== $key) {
                    $this->properties[$className][$key] = $this->accessor->getValue($instance, $prop);

                    continue;
                }

                foreach ($prop as $element) {
                    try {
                        $value = $this->accessor->getValue($instance, $element['value']);

                        if ($value instanceof \DateTimeInterface) {
                            $value = $value->format(DATE_ATOM);
                        }

                        $this->properties[$className][$element['key']] = $value;
                    } catch (\Exception $e) {
                        if ($this->displayErrors) {
                            throw $e;
                        }

                        $this->properties[$className][$element['key']] = null;
                    }
                }
            }

            return $this->properties[$className];
        }

        return [];
    }

    /**
     * @param object|null $instance
     *
     * @return object|null
     */
    public function getUser($instance = null)
    {
        if (null !== $instance) {
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
