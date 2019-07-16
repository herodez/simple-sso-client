<?php

namespace Optime\SimpleSsoClientBundle\External;

use Optime\SimpleSsoClientBundle\Security\Provider\SimpleSsoServerProvider;
use Optime\SimpleSsoClientBundle\Security\Provider\SsoServerProviderInterface;

/**
 * @author Manuel Aguirre <programador.manuel@gmail.com>
 */
class RemoteConnection implements RemoteConnectionInterface
{
    /**
     * @var  
     */
    private $serverProvider;
    
    /**
     * RemoteConnection constructor.
     * @param $defaultServerId
     */
    public function __construct(SsoServerProviderInterface $serverProvider)
    {
        $this->serverProvider = $serverProvider;
    }
    
    public function getServerId()
    {
       return $this->serverProvider->getCurrentServer()->getServerId(); 
    }
    
    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->serverProvider->getCurrentServer()->getUsername();
    }
    
    /**
     * @return string
     */
    public function getPrivateKey()
    {
        return $this->serverProvider->getCurrentServer()->getPassword();
    }
    
    /**
     * @return string
     */
    public function getLoginUrl()
    {
        return rtrim($this->serverProvider->getCurrentServer()->getUrl()) . '/login';
    }
    
    /**
     * @return string
     */
    public function getVerificationUrl()
    {
        return rtrim($this->serverProvider->getCurrentServer()->getUrl()) . '/verify';
    }
}
    
