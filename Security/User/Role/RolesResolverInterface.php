<?php

namespace Optime\SimpleSsoClientBundle\Security\User\Role;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @author Manuel Aguirre <programador.manuel@gmail.com>
 */
interface RolesResolverInterface
{
    public function getRoles(UserInterface $user);
}