<?php

namespace Optime\SimpleSsoClientBundle\DependencyInjection\Compiler;

use Optime\SimpleSsoClientBundle\External\RemoteConnectionInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * @author Manuel Aguirre <programador.manuel@gmail.com>
 */
class RemoteConnectionPass implements CompilerPassInterface
{

    public function process(ContainerBuilder $container)
    {
        if (!$container->hasParameter('simple_sso_client.remote_connection.service')) {
            return;
        }

        $serviceId = $container->getParameter('simple_sso_client.remote_connection.service');

        try {
            $def = $container->findDefinition($serviceId);

            if ($class = $def->getClass()) {
                if (!is_subclass_of($class, RemoteConnectionInterface::class)) {
                    throw new \InvalidArgumentException(sprintf(
                        'The sso remote connection class "%s" must implement %s',
                        $class,
                        RemoteConnectionInterface::class
                    ));
                }
            }

        } catch (ServiceNotFoundException $e) {
            throw new \InvalidArgumentException(
                sprintf('The sso remote connection service: "%s" does not exists', $serviceId),
                $e->getCode(),
                $e->getPrevious()
            );
        }
    }
}