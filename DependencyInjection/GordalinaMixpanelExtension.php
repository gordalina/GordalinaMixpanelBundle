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

namespace Gordalina\MixpanelBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class GordalinaMixpanelExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration($container->getParameter('kernel.debug'));
        $config        = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $this->loadRegistry($config, $container);
        $this->loadParameters($config, $container);
    }

    private function loadRegistry(array $config, ContainerBuilder $container)
    {
        if (array_key_exists('default', $config['projects'])) {
            $default = 'default';
        } else {
            $default = key($config['projects']);
        }

        $registry = $container->getDefinition('gordalina_mixpanel.registry');

        foreach ($config['projects'] as $name => $project) {
            $id = "gordalina_mixpanel.{$name}";

            $container
                ->register($id, 'Mixpanel')
                ->addArgument($project['token'])
                ->addArgument($project['options']);

            $container->setAlias("mixpanel.{$name}", $id);
            $registry->addMethodCall(
                'addProject',
                [$id, "mixpanel.{$name}", new Reference($id)]
            );
            $registry->addMethodCall('setConfig', [$id, $project]);
        }

        $container->setAlias('mixpanel.default', "gordalina_mixpanel.{$default}");
        $container->setAlias('mixpanel', "gordalina_mixpanel.{$default}");

        $registry->addMethodCall('addAlias', ['mixpanel.default', "gordalina_mixpanel.{$default}"]);
        $registry->addMethodCall('addAlias', ['mixpanel', "gordalina_mixpanel.{$default}"]);

        foreach ($config['users'] as $class => $user) {
            $registry->addMethodCall('addUser', [$class, $user]);
        }
    }

    private function loadParameters(array $config, ContainerBuilder $container)
    {
        $container->setParameter('gordalina_mixpanel.enable_profiler', $config['enable_profiler']);
    }
}
