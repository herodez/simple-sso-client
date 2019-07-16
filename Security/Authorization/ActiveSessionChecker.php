<?php

namespace Optime\SimpleSsoClientBundle\Security\Authorization;

use Optime\SimpleSsoClientBundle\External\RemoteConnectionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @author Manuel Aguirre <programador.manuel@gmail.com>
 */
class ActiveSessionChecker
{
    /**
     * @var RemoteConnectionVerifier
     */
    private $verifier;
    /**
     * @var RemoteConnectionInterface
     */
    private $connection;
    
    /**
     * @param RemoteConnectionVerifier $verifier
     * @param RemoteConnectionInterface $connection
     */
    public function __construct(RemoteConnectionVerifier $verifier, RemoteConnectionInterface $connection)
    {
        $this->verifier = $verifier;
        $this->connection = $connection;
    }
    
    public function verifySession(TokenInterface $token)
    {
        try {
            $otp = $token->getAttribute('_sso_otp');
        } catch (\InvalidArgumentException $e) {
            return false;
        }
        
        if (null == $otp) {
            return false;
        }
        
        $created = date('dmY');
        $password = base64_encode(sha1($otp . $created . $this->connection->getPrivateKey()));
        
        return $this->verifier->verify($otp, $created, $password);
    }
}