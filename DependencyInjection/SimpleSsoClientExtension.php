<?php

namespace Optime\SimpleSsoClientBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class SimpleSsoClientExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        
        $container->findDefinition('simple_sso_client.security.roles_resolver.default')
            ->replaceArgument(0, $config['roles_from_profile']);
        
        $this->configureRemoteConnectionService($container, $config);
        $this->configureExternalRepositoryService($container, $config);
        $this->configureServerProviderService($container, $config);
        $this->configureServerIdOnPath($container, $config);
        
    }
    
    /**
     * @param ContainerBuilder $container
     * @param $config
     */
    protected function configureRemoteConnectionService(ContainerBuilder $container, $config)
    {
        $serviceId = $config['remote_connection_service'];
        if ($serviceId !== 'simple_sso_client.default_remote_connection') {
            $container->removeDefinition('simple_sso_client.default_remote_connection');
            $container->setParameter('simple_sso_client.remote_connection.service', $serviceId);
        }
        
        $container->setAlias('simple_sso_client.remote_connection', $serviceId);
    }
    
    /**
     * @param ContainerBuilder $container
     * @param $config
     */
    protected function configureExternalRepositoryService(ContainerBuilder $container, $config)
    {
        $container->setAlias('simple_sso_client.security_user.user_factory', $config['user_factory']);
    }
    
    /**
     * @param ContainerBuilder $container
     * @param $config
     */
    protected function configureServerProviderService(ContainerBuilder $container, $config)
    {
        $container->findDefinition('simple_sso_client.security_provider.simple_sso_server_provider')
            ->replaceArgument(2, $config['servers'])
            ->replaceArgument(3, $config['default_server']);
    }
    
    /**
     * @param ContainerBuilder $container
     * @param array $config
     */
    private function configureServerIdOnPath(ContainerBuilder $container, array $config)
    {
        if(!$config['server_id_on_path']){
            $container->removeDefinition('simple_sso_client.event_listener.server_id_listener');
        }
    }
}
