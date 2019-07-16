<?php

namespace Optime\SimpleSsoClientBundle\Security\Provider;

use Optime\SimpleSsoClientBundle\Security\User\ExternalUserInterface;
use Optime\SimpleSsoClientBundle\Security\User\UserFactoryInterface;
use Optime\SimpleSsoClientBundle\Security\ExternalUserRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

/**
 * @author Manuel Aguirre <programador.manuel@gmail.com>
 */
class SimpleSsoUserProvider implements UserProviderInterface
{
    /**
     * @var ExternalUserRepository
     */
    private $externalUserRepository;
    
    /**
     * SimpleSsoUserProvider constructor.
     * @param ExternalUserRepository $externalUserRepository
     * @throws \Exception
     */
    public function __construct(ExternalUserRepository $externalUserRepository)
    {
        $this->externalUserRepository = $externalUserRepository;
    }

    public function loadUserByUsername($otp)
    {
        return $this->externalUserRepository->find($otp);
    }
    
    public function refreshUser(UserInterface $user)
    {
        return $user;
    }

    public function supportsClass($class)
    {
        return $class instanceof ExternalUserInterface;
    }
}