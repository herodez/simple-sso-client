<?php

namespace Optime\SimpleSsoClientBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('simple_sso_client');
        
        $rootNode
            ->validate()
                ->ifTrue(function($v){ return $v['remote_connection_service'] ==  'simple_sso_client.default_remote_connection'; })
                ->then(function($v) {
                    // Si se está usando el servicio por defecto, los parametros:
                    // username, password, url son requeridos
    
                    if (!isset($v['servers'])) {
                        throw new \InvalidArgumentException(
                            sprintf('The key "%s" is required', 'servers')
                        );
                    }
    
                    foreach ($v['servers'] as $server) {
                        foreach (['username', 'password', 'url', 'server_id'] as $key) {
                            if (empty($server[$key])) {
                                throw new \InvalidArgumentException(
                                    sprintf('The key "%s" is required', $key)
                                );
                            }
                        }
                    }
    
                    $serverIds = [];
                    foreach ($v['servers'] as $server) {
                        if (in_array($server['server_id'], $serverIds, true)) {
                            throw new \InvalidArgumentException(
                                sprintf('Duplicate server id', $server['server_id'])
                            );
                        }
        
                        $serverIds[] = $server['server_id'];
                    }
                    
                    return $v;
                })
            ->end()
            ->children()
                ->arrayNode('servers')
                    ->prototype('array')
                       ->children()
                            ->scalarNode('server_id')->defaultNull()->cannotBeEmpty()->end()
                            ->scalarNode('username')->defaultNull()->cannotBeEmpty()->end()
                            ->scalarNode('password')->defaultNull()->cannotBeEmpty()->end()
                            ->scalarNode('url')->cannotBeEmpty()->defaultNull()->end()
                       ->end()
                    ->end()
                ->end()
                ->booleanNode('roles_from_profile')->defaultFalse()->end()
                ->scalarNode('remote_connection_service')
                    ->cannotBeEmpty()
                    ->defaultValue('simple_sso_client.default_remote_connection')
                ->end()
                ->scalarNode('roles_resolver_service')
                    ->cannotBeEmpty()
                    ->defaultValue('simple_sso_client.security.roles_resolver.default')
                ->end()
                ->scalarNode('default_server')
                    ->cannotBeEmpty()
                    ->defaultValue(null)
                ->end()
                ->scalarNode('user_factory')
                    ->cannotBeEmpty()
                    ->defaultValue(null)
                ->end()
                ->scalarNode('login_form_path')
                    ->cannotBeEmpty()
                    ->defaultValue(null)
                ->end()
            ->end();

        return $treeBuilder;
    }
}
