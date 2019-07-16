<?php

namespace SimpleSsoClientBundleTests\External;

use Optime\SimpleSsoClientBundle\External\RemoteConnection;
use Optime\SimpleSsoClientBundle\External\RemoteConnectionInterface;

/**
 * @author Manuel Aguirre <programador.manuel@gmail.com>
 */
class RemoteConnectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RemoteConnection
     */
    private $connection;
    private $username = 'username-test';
    private $privateKey = 'private-key-test';
    private $baseUrl = 'base-url-test';

    public function setUp()
    {
        $this->connection = new RemoteConnection(
            $this->username,
            $this->privateKey,
            $this->baseUrl
        );
    }

    public function testImplementsCorrectInterface()
    {
        $this->assertInstanceOf(RemoteConnectionInterface::class, $this->connection);
    }

    public function testGetUsername()
    {
        $username = $this->connection->getUsername();

        $this->assertSame($this->username, $username);
    }

    public function testGetPrivateKey()
    {
        $privateKey = $this->connection->getPrivateKey();

        $this->assertSame($this->privateKey, $privateKey);
    }

    public function testGetLoginUrl()
    {
        $url = $this->connection->getLoginUrl();

        $this->assertSame($this->baseUrl.'/login', $url);
    }

    public function testGetVerificationUrl()
    {
        $url = $this->connection->getVerificationUrl();

        $this->assertSame($this->baseUrl.'/verify', $url);
    }

    public function testThrowExceptionOnNullUrl()
    {
        $this->setExpectedExceptionRegExp(\InvalidArgumentException::class, '/baseUrl/i');

        new RemoteConnection($this->username, $this->privateKey, null);
    }

    public function testThrowExceptionOnNullUsername()
    {
        $this->setExpectedExceptionRegExp(\InvalidArgumentException::class, '/username/i');

        new RemoteConnection(null, $this->privateKey, $this->baseUrl);
    }

    public function testThrowExceptionOnNullPrivateKey()
    {
        $this->setExpectedExceptionRegExp(\InvalidArgumentException::class, '/privateKey/i');

        new RemoteConnection($this->username, null, $this->baseUrl);
    }
}
