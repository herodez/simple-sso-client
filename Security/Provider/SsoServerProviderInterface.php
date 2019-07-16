<?php


namespace Optime\SimpleSsoClientBundle\Security\Provider;


use Optime\SimpleSsoClientBundle\Security\Server\Server;

interface SsoServerProviderInterface
{
    /**
     * @return Server
     */
    public function getCurrentServer();
    
    /**
     * @return array
     */
    public function getServers();
    
    /**
     * @param string $id
     * @return Server
     */
    public function getServerById($id);
}