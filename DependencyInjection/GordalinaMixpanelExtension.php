<?php

/*
 * This file is part of the mixpanel bundle.
 *
 * (c) Samuel Gordalina <https://github.com/gordalina/mixpanel-bundle>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gordalina\MixpanelBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Stopwatch\Stopwatch;

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
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $this->registerClassesToCompile();
        $this->loadRegistry($config, $container);
        $this->loadParameters($config, $container);
    }

    /**
     * @return null
     */
    private function registerClassesToCompile()
    {
        $this->addClassesToCompile(array(
            'Gordalina\MixpanelBundle\Annotation\Annotation',
            'Gordalina\MixpanelBundle\EventListener\AuthenticationListener',
            'Gordalina\MixpanelBundle\EventListener\ControllerListener',
            'Gordalina\MixpanelBundle\EventListener\FinishRequestListener',
            'Gordalina\MixpanelBundle\Mixpanel\Flusher',
            'Gordalina\MixpanelBundle\ManagerRegistry',
            'Gordalina\MixpanelBundle\Security\Authentication',
            'Gordalina\MixpanelBundle\Security\UserData',
        ));
    }

    /**
     * @param  array            $config
     * @param  ContainerBuilder $container
     * @return null
     */
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
                array($id, "mixpanel.{$name}", new Reference($id))
            );
            $registry->addMethodCall('setConfig', array($id, $project));
        }

        $container->setAlias("mixpanel.default", "gordalina_mixpanel.{$default}");
        $container->setAlias("mixpanel", "gordalina_mixpanel.{$default}");

        $registry->addMethodCall('addAlias', array("mixpanel.default", "gordalina_mixpanel.{$default}"));
        $registry->addMethodCall('addAlias', array("mixpanel", "gordalina_mixpanel.{$default}"));

        foreach ($config['users'] as $class => $user) {
            $registry->addMethodCall('addUser', array($class, $user));
        }
    }

    /**
     * @param  array            $config
     * @param  ContainerBuilder $container
     * @return null
     */
    private function loadParameters(array $config, ContainerBuilder $container)
    {
        $container->setParameter('gordalina_mixpanel.enable_profiler', $config['enable_profiler']);
    }
}
