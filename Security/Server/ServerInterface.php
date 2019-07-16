<?php


namespace Optime\SimpleSsoClientBundle\Security\Server;


interface ServerInterface
{
    /**
     * @return string
     */
    public function getServerId();
    
    /**
     * @return string 
     */    
    public function getServerName();
    
    /**
     * @return string
     */
    public function getUsername();
        
    /**
     * @return string
     */
    public function getPassword();
    
    /**
     * @return string
     */
    public function getUrl();
}