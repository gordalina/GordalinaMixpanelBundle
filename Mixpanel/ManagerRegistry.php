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

namespace Gordalina\MixpanelBundle\Mixpanel;

class ManagerRegistry
{
    /**
     * @var array
     */
    private $projects = [];

    /**
     * @var array
     */
    private $alias = [];

    /**
     * @var array
     */
    private $config = [];

    /**
     * @var array<string, string>
     */
    private $users = [];

    /**
     * @param string $id
     * @param string $alias
     */
    public function addProject($id, $alias, \Mixpanel $project)
    {
        $this->projects[$id] = $project;
        $this->addAlias($alias, $id);

        return $this;
    }

    /**
     * @param string $alias
     * @param string $id
     */
    public function addAlias($alias, $id)
    {
        $this->alias[$alias] = $id;
    }

    /**
     * @param string $class
     * @param string $property
     */
    public function addUser($class, $property)
    {
        $this->users[$class] = $property;
    }

    /**
     * @param string $id
     */
    public function setConfig($id, array $config)
    {
        $this->config[$id] = $config;
    }

    /**
     * @return array
     */
    public function getProjects()
    {
        return $this->projects;
    }

    /**
     * @return \Mixpanel
     */
    public function getProject($id)
    {
        if (isset($this->projects[$id])) {
            return $this->projects[$id];
        }

        throw new \LogicException(sprintf('Cannot find project with id "%s"', $id));
    }

    /**
     * @return array
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return array<string, string>
     */
    public function getUsers(): array
    {
        return $this->users;
    }
}
