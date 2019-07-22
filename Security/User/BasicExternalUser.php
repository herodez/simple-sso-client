<?php


namespace Optime\SimpleSsoClientBundle\Security\User;


use Symfony\Component\Security\Core\User\UserInterface;

class BasicExternalUser implements UserInterface, ExternalUserInterface
{
    /**
     * @var string
     */
    private $username;
    
    /**
     * @var array
     */
    private $securityRoles;
    
    
    public static function createFromArray(
        $data
    ) {
        $user = new static();
        
        isset($data['user']['username']) and $user->setUsername($data['user']['username']);
        isset($data['security_roles']) and $user->setSecurityRoles($data['security_roles']);
        
        return $user;
    }
    
    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }
    
    /**
     * @param $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }
    
    /**
     * @return array
     */
    public function getRoles()
    {
        return (array)$this->getSecurityRoles();
    }
    
    /**
     * @param array $securityRoles
     * @return $this
     */
    public function setSecurityRoles($securityRoles)
    {
        $this->securityRoles = $securityRoles;
        
        return $this;
    }
    
    /**
     * @return array
     */
    public function getSecurityRoles()
    {
        return $this->securityRoles;
    }
    
    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getUsername();
    }
    
    public function getPassword()
    {
    }
    
    public function getSalt()
    {
    }
    
    public function eraseCredentials()
    {
    }
}