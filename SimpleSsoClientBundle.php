<?php

namespace Optime\SimpleSsoClientBundle;

use Optime\SimpleSsoClientBundle\DependencyInjection\Compiler\RemoteConnectionPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SimpleSsoClientBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new RemoteConnectionPass());
    }
}
