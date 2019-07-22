<?php

namespace Optime\SimpleSsoClientBundle\Security;

use Optime\SimpleSsoClientBundle\Security\User\UserFactoryInterface;
use Doctrine\ORM\EntityManager;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ServerException;
use Optime\SimpleSsoClientBundle\External\RemoteConnectionInterface;
use Optime\SimpleSsoClientBundle\Security\User\ExternalUserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

/**
 * @author Manuel Aguirre <programador.manuel@gmail.com>
 */
class ExternalUserRepository
{
    /**
     * @var ClientInterface
     */
    private $httpClient;
    
    /**
     * @var RemoteConnectionInterface
     */
    private $remoteConnection;
    
    /**
     * @var UserFactoryInterface 
     */
    private $userFactory;
    
    /**
     * SimpleSsoUserProvider constructor.
     * @param ClientInterface $httpClient
     * @param $password
     */
    public function __construct(
        ClientInterface $httpClient,
        RemoteConnectionInterface $remoteConnection,
        UserFactoryInterface $userFactory
    ) {
        $this->httpClient = $httpClient;
        $this->remoteConnection = $remoteConnection;
        $this->userFactory = $userFactory;
    }
    
    /**
     * @param $otp
     * @return ExternalUserInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function find($otp)
    {
        $created = date('Ymdhis');
        $hash = base64_encode(sha1($otp . $created . $this->remoteConnection->getPrivateKey()));
       
        try {
            $response = $this->httpClient->request('POST',
                $this->remoteConnection->getLoginUrl(), [
                    'form_params' => [
                        'otp' => $otp,
                        'created' => $created,
                        'password' => $hash,
                    ],
                ]);
        } catch (ServerException $e) {
            throw new UsernameNotFoundException($e);
        }
        
        $data = unserialize(base64_decode($response->getBody()));
        return $this->userFactory->createFromRemoteData($data);
    }
}