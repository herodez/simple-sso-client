<?php
/*
 * This file is part of the Manuel Aguirre Project.
 *
 * (c) Manuel Aguirre <programador.manuel@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Optime\SimpleSsoClientBundle\Security\Authorization;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Optime\SimpleSsoClientBundle\External\RemoteConnectionInterface;

/**
 * @author Manuel Aguirre <programador.manuel@gmail.com>
 */
class RemoteConnectionVerifier
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
     * RemoteConnectionVerifier constructor.
     * @param ClientInterface $httpClient
     * @param RemoteConnectionInterface $remoteConnection
     */
    public function __construct(ClientInterface $httpClient, RemoteConnectionInterface $remoteConnection)
    {
        $this->httpClient = $httpClient;
        $this->remoteConnection = $remoteConnection;
    }

    public function verify($otp, $created, $password)
    {
        try {
            $result = $this->httpClient->request('GET', $this->remoteConnection->getVerificationUrl(), [
                'query' => [
                    'otp' => $otp,
                    'created' => $created,
                    'password' => $password,
                ],
            ]);
        } catch (RequestException $e) {
            return false;
        }

        return $result->getBody() == '1';
    }
}