<?php


namespace Optime\SimpleSsoClientBundle\Security\Provider;


use Optime\SimpleSsoClientBundle\Security\Exception\NotSsoServerExits;
use Optime\SimpleSsoClientBundle\Security\Server\Server;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class SimpleSsoServerProvider implements SsoServerProviderInterface
{
    /**
     * @var Server[]
     */
    private $servers;
    
    /**
     * @var RequestStack
     */
    private $requestStack;
    
    /**
     * @var string
     */
    private $defaultServer;
    
    /**
     * @var TokenStorage
     */
    private $token;
    
    
    public function __construct(RequestStack $requestStack, TokenStorage $token, array $servers, $defaultServer)
    {
        $this->requestStack = $requestStack;
        $this->servers = $this->createSsoServers($servers);
        $this->token = $token;
        $this->defaultServer = $defaultServer;
    }
    
    /**
     * @return Server
     * @throws NotSsoServerExits
     */
    public function getCurrentServer()
    {
        if ($this->token->getToken() !== null && $this->token->getToken()->hasAttribute('_sso_server_id')) {
            return $this->getServerById($this->token->getToken()->getAttribute('_sso_server_id'));
        } elseif($serverId = $this->requestStack->getCurrentRequest()->get('_sso_server_id')){
            return $this->getServerById($serverId);
        } elseif ($this->defaultServer !== null) {
            return $this->getServerByName($this->defaultServer);
        } else {
            throw new \LogicException('Parameter _sso_server_id not found in the request.');
        }
    }
    
    /**
     * @param $name
     * @return Server
     * @throws NotSsoServerExits
     */
    public function getServerByName($name)
    {
        return $this->getServerBy('getServerName', $name);
    }
    
    /**
     * @param $id
     * @return mixed|Server
     * @throws NotSsoServerExits
     */
    public function getServerById($id)
    {
        return $this->getServerBy('getServerId', $id);
    }
    
    /**
     * @param $method
     * @param $value
     * @return mixed|Server
     * @throws NotSsoServerExits
     */
    public function getServerBy($method, $value)
    {
        /** @var Server $server */
        foreach ($this->servers as $server) {
            if ($server->$method() === $value) {
                return $server;
            }
        }
        
        throw new NotSsoServerExits("Server not exits");
    }
    
    /**
     * @return array
     */
    public function getServers()
    {
        return $this->servers;
    }
    
    /**
     * @param $configServers
     * @return array
     */
    private function createSsoServers($configServers)
    {
        $servers = [];
        foreach ($configServers as $serverName => $configServer) {
            $servers[] = new Server($configServer['server_id'], $serverName, $configServer['username'],
                $configServer['password'],
                $configServer['url']);
        }
        
        return $servers;
    }
}