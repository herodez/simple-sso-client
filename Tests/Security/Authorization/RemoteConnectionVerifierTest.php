<?php

namespace SimpleSsoClientBundleTests\Security\Authorization;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Message\ResponseInterface;
use Optime\SimpleSsoClientBundle\External\ExternalUrlResolverInterface;
use Optime\SimpleSsoClientBundle\External\RemoteConnectionInterface;
use Optime\SimpleSsoClientBundle\Security\Authorization\RemoteConnectionVerifier;
use Prophecy\Argument;

/**
 * @author Manuel Aguirre <programador.manuel@gmail.com>
 */
class RemoteConnectionVerifierTest extends \PHPUnit_Framework_TestCase
{
    private $response;
    /**
     * @var RemoteConnectionVerifier
     */
    private $verifier;
    private $httpClient;
    private $remoteConnection;
    private $verificationUrl = 'http://127.0.0.1/test';

    public function setUp()
    {
        $this->httpClient = $this->prophesize(ClientInterface::class);
        $this->remoteConnection = $this->prophesize(RemoteConnectionInterface::class);
        $this->remoteConnection->getVerificationUrl()->willReturn($this->verificationUrl);

        $this->response = $this->prophesize(ResponseInterface::class);

        $this->verifier = new RemoteConnectionVerifier(
            $this->httpClient->reveal(), $this->remoteConnection->reveal()
        );
    }

    public function testVerify_ReturnTrue()
    {
        $this->httpClient
            ->get($this->verificationUrl, Argument::type('array'))
            ->willReturn($this->response->reveal())
            ->shouldBeCalled();

        $this->response->getBody()->willReturn('1');

        $result = $this->verifier->verify('otp-test', date('dmY'), 'created-password');

        $this->assertTrue($result);
    }

    public function testVerify_ReturnFalse()
    {
        $this->httpClient
            ->get($this->verificationUrl, Argument::type('array'))
            ->willReturn($this->response->reveal())
            ->shouldBeCalled();

        $this->response->getBody()->willReturn('0');

        $result = $this->verifier->verify('otp-test', date('dmY'), 'created-password');

        $this->assertFalse($result);
    }

    public function testVerify_ReturnFalseOnRemoteError()
    {
        $this->httpClient
            ->get($this->verificationUrl, Argument::type('array'))
            ->willThrow(RequestException::class)
            ->shouldBeCalled();

        $result = $this->verifier->verify('otp-test', date('dmY'), 'created-password');

        $this->assertFalse($result);
    }
}
