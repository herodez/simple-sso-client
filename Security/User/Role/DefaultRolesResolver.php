<?php

namespace Optime\SimpleSsoClientBundle\Security\User\Role;

use Optime\SimpleSsoClientBundle\Security\User\ExternalUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @author Manuel Aguirre <programador.manuel@gmail.com>
 */
class DefaultRolesResolver implements RolesResolverInterface
{
    /**
     * @var bool
     */
    private $rolesFromProfile;

    /**
     * DefaultRolesResolver constructor.
     * @param bool $rolesFromProfile
     */
    public function __construct($rolesFromProfile)
    {
        $this->rolesFromProfile = $rolesFromProfile;
    }

    public function getRoles(UserInterface $user)
    {
        if ($user instanceof ExternalUserInterface and $this->rolesFromProfile) {
            return $user->getRolesByProfile();
        }

        return $user->getRoles();
    }
}