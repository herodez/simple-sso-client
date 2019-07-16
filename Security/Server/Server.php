<?php

namespace Optime\SimpleSsoClientBundle\Security\Server;


class Server implements ServerInterface
{
    private $serverId;
    private $serverName;
    private $username;
    private $password;
    private $url;
    
    /**
     * Server constructor.
     * @param $serverId
     * @param $serverName
     * @param $username
     * @param $password
     * @param $url
     */
    public function __construct($serverId, $serverName, $username, $password, $url)
    {
        $this->serverId = $serverId;
        $this->serverName = $serverName;
        $this->username = $username;
        $this->password = $password;
        $this->url = $url;
    }
    
    /**
     * @return string
     */
    public function getServerId()
    {
        return $this->serverId;
    }
    
    public function getServerName()
    {
       return $this->serverName; 
    }
    
    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }
    
    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }
    
    /**
     * @return string
     */
    public function getUrl()
    {
       return $this->url; 
    }
}