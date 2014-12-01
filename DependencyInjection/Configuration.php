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

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * @var boolean
     */
    private $debug;

    /**
     * @param boolean $debug
     */
    public function __construct($debug)
    {
        $this->debug = $debug;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('gordalina_mixpanel');

        $rootNode
            ->children()
                ->booleanNode('enable_profiler')
                    ->defaultValue($this->debug)
                ->end()
                ->arrayNode('users')
                    ->useAttributeAsKey('class')
                    ->prototype('array')
                        ->beforeNormalization()
                            ->ifTrue(function ($v) { return is_string($v) && strlen($v) > 0; })
                            ->then(function ($v) {
                                return array('id' => $v);
                            })
                        ->end()
                        ->children()
                            ->scalarNode('id')->isRequired()->end()
                            ->scalarNode('first_name')->end()
                            ->scalarNode('last_name')->end()
                            ->scalarNode('email')->end()
                            ->scalarNode('phone')->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('projects')
                    ->useAttributeAsKey('name')
                    ->requiresAtLeastOneElement()
                    ->prototype('array')
                        ->children()
                            ->scalarNode('token')->isRequired()->end()
                            ->append($this->addMixpanelOptionsNode())
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }

    public function addMixpanelOptionsNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('options');

        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->integerNode('max_batch_size')
                    ->min(1)
                    ->max(50)
                ->end()
                ->integerNode('max_queue_size')
                    ->min(1)
                ->end()
                ->booleanNode('debug')->end()
                ->scalarNode('consumer')->defaultValue('curl')->end()
                ->arrayNode('consumers')
                    ->requiresAtLeastOneElement()
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->beforeNormalization()
                            ->ifTrue(function ($v) { return is_string($v) && strlen($v) > 0; })
                            ->then(function ($v) {
                                return array('class' => $v);
                            })
                        ->end()
                        ->children()
                            ->scalarNode('class')->isRequired()->end()
                        ->end()
                    ->end()
                    ->validate()
                        ->always()
                        ->then(function ($consumers) {
                            return array_map(function ($consumer) {
                                return $consumer['class'];
                            }, $consumers);
                        })
                    ->end()
                ->end()
                ->scalarNode('host')->end()
                ->scalarNode('events_endpoint')->end()
                ->scalarNode('people_endpoint')->end()
                ->booleanNode('use_ssl')->end()
                ->scalarNode('error_callback')->end()
            ->end()
        ;

        return $node;
    }
}
