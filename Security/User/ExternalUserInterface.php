<?php

namespace Optime\SimpleSsoClientBundle\Security\User;

/**
 * @author Manuel Aguirre <programador.manuel@gmail.com>
 */
interface ExternalUserInterface
{
    public function getUsername();
    public function getRoles();
}