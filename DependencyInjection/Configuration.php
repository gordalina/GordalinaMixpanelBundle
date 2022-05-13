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

use Symfony\Component\Config\Definition\BaseNode;
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
     * @var bool
     */
    private $debug;

    /**
     * @param bool $debug
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
        $treeBuilder = new TreeBuilder('gordalina_mixpanel');
        $rootNode    = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->booleanNode('enabled')
                    ->info('Send Data to Mixpanel')
                    ->defaultValue(true)
                ->end()
                ->booleanNode('send_user_ip')
                    ->info('Send user ip as part of the user data')
                    ->defaultValue(true)
                ->end()
                ->booleanNode('auto_update_user')
                    ->info('Send data user on each master request (useful if you do not force users to disconnect when setting up the bundle or add new properties in their profile. WARNING: preferred used @Mixpanel\UpdateUser() or @Mixpanel\Set() at connection for performances)')
                    ->defaultValue($this->debug)
                ->end()
                ->booleanNode('throw_on_user_data_attribute_failure')
                    ->info('Throw Exception if user attribute does not exist')
                    ->defaultValue($this->debug)
                ->end()
                ->booleanNode('enable_profiler')
                    ->defaultValue($this->debug)
                ->end()
                ->arrayNode('users')
                    ->useAttributeAsKey('class')
                    ->prototype('array')
                        ->beforeNormalization()
                            ->ifTrue(function ($v) { return is_string($v) && strlen($v) > 0; })
                            ->then(function ($v) {
                                return ['id' => $v];
                            })
                            ->ifTrue(function ($keys) {
                                foreach ($keys as $key => $value) {
                                    if (isset($keys['$'.$key])) {
                                        return true;
                                    }
                                }

                                return false;
                            })
                            ->thenInvalid('Duplicated property found in %s. If this variable is a MixPanel reserved property, please prefix it by "$".')
                        ->end()
                        ->children()
                            ->scalarNode('id')->isRequired()->end()
                            ->scalarNode('first_name')
                                ->setDeprecated(...$this->getDeprecationParams('The "%node%" option at path "%path%" is deprecated. Use "$first_name" instead.'))
                            ->end()
                            ->scalarNode('$first_name')->end()
                            ->scalarNode('last_name')
                                ->setDeprecated(...$this->getDeprecationParams('The "%node%" option at path "%path%" is deprecated. Use "$last_name" instead.'))
                            ->end()
                            ->scalarNode('$last_name')->end()
                            ->scalarNode('email')
                                ->setDeprecated(...$this->getDeprecationParams('The "%node%" option at path "%path%" is deprecated. Use "$email" instead.'))
                            ->end()
                            ->scalarNode('$email')->end()
                            ->scalarNode('phone')
                                ->setDeprecated(...$this->getDeprecationParams('The "%node%" option at path "%path%" is deprecated. Use "$phone" instead.'))
                            ->end()
                            ->scalarNode('$phone')->end()
                            ->arrayNode('extra_data')
                                ->info('Non-default properties in Mixpanel user profile')
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('key')->isRequired()->end()
                                        ->scalarNode('value')->isRequired()->end()
                                    ->end()
                                ->end()
                            ->end()
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
        $treeBuilder = new TreeBuilder('options');
        $rootNode    = $treeBuilder->getRootNode();

        $rootNode
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
                                return ['class' => $v];
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

        return $rootNode;
    }

    /**
     * @return array<int,string>
     */
    private function getDeprecationParams(string $message): array
    {
        if (method_exists(BaseNode::class, 'getDeprecation')) {
            return [
                'gordalina/mixpanel-bundle',
                '3.2',
                $message,
            ];
        }

        return [$message];
    }
}
