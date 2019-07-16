<?php

namespace SimpleSsoClientBundleTests\Security;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Message\ResponseInterface;
use Optime\SimpleSsoClientBundle\External\RemoteConnectionInterface;
use Optime\SimpleSsoClientBundle\ExternalUserInterface;
use Optime\SimpleSsoClientBundle\Security\ExternalUserRepository;
use Prophecy\Argument;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

/**
 * @author Manuel Aguirre <programador.manuel@gmail.com>
 */
class ExternalUserRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ExternalUserRepository
     */
    private $repository;
    private $client;
    private $loginUrl = 'http://login-url/test';
    private $privateKey = 'password-test';
    private $remoteConnection;

    public function setUp()
    {
        $this->client = $this->prophesize(ClientInterface::class);
        $this->remoteConnection = $this->prophesize(RemoteConnectionInterface::class);
        $this->remoteConnection->getLoginUrl()->willReturn($this->loginUrl);
        $this->remoteConnection->getPrivateKey()->willReturn($this->privateKey);

        $this->repository = new ExternalUserRepository(
            $this->client->reveal(), $this->remoteConnection->reveal()
        );
    }

    public function testFind_ReturnCorrectInstance()
    {
        $response = $this->prophesize(ResponseInterface::class);
        $response->getBody()->willReturn($this->getValidResponse());

        $this->client->post(Argument::cetera())->willReturn(
            $response->reveal()
        );

        $user = $this->repository->find('otp-test');

        $this->assertInstanceOf(ExternalUserInterface::class, $user);
    }

    public function testFind_ExceptionOnInvalidResponseCompany()
    {
        $this->setExpectedExceptionRegExp(
            AuthenticationCredentialsNotFoundException::class,
            '/company/'
        );

        $response = $this->prophesize(ResponseInterface::class);
        $response->getBody()->willReturn($this->getInvalidCompanyResponse());

        $this->client->post(Argument::cetera())->willReturn(
            $response->reveal()
        );

        $this->repository->find('otp-test');
    }

    public function testFind_ExceptionOnInvalidResponseUser()
    {
        $this->setExpectedExceptionRegExp(
            AuthenticationCredentialsNotFoundException::class,
            '/user/'
        );

        $response = $this->prophesize(ResponseInterface::class);
        $response->getBody()->willReturn($this->getInvalidUserResponse());

        $this->client->post(Argument::cetera())->willReturn(
            $response->reveal()
        );

        $this->repository->find('otp-test');
    }

    public function testFind_ExceptionOnInvalidResponseProfile()
    {
        $this->setExpectedExceptionRegExp(
            AuthenticationCredentialsNotFoundException::class,
            '/profile/'
        );

        $response = $this->prophesize(ResponseInterface::class);
        $response->getBody()->willReturn($this->getInvalidProfileResponse());

        $this->client->post(Argument::cetera())->willReturn(
            $response->reveal()
        );

        $this->repository->find('otp-test');
    }

    public function testFind_CheckPassedUrl()
    {
        $response = $this->prophesize(ResponseInterface::class);
        $response->getBody()->willReturn($this->getValidResponse());

        $this->client->post($this->loginUrl, Argument::cetera())->willReturn(
            $response->reveal()
        )->shouldBeCalled();

        $this->repository->find('otp-test');
    }

    public function testFind_CheckCreatedPasswordAlgorithm()
    {
        $response = $this->prophesize(ResponseInterface::class);
        $response->getBody()->willReturn($this->getValidResponse());

        $parameters = null;

        $this->client->post($this->loginUrl, Argument::that(function($data) use (&$parameters){

            $parameters = $data['body'];

            return true;
        }))->willReturn(
            $response->reveal()
        )->shouldBeCalled();

        $this->repository->find('otp-test');

        $expectedPassword = base64_encode(sha1(
            $parameters['otp'].$parameters['created'].$this->privateKey
        ));

        $this->assertSame($expectedPassword, $parameters['password']);
    }

    /**
     * @return string
     */
    protected function getValidResponse()
    {
        return base64_encode(serialize([
            'company' => [
                'id' => 'valor',
                'name' => 'valor',
            ],
            'user' => [
                'id' => 'valor',
                'email' => 'valor',
                'username' => 'valor',
                'first_name' => 'valor',
                'last_name' => 'valor',
            ],
            'profile' => [
                'id' => 'valor',
                'name' => 'valor',
            ],
            'security_roles' => ['ROLE_SALES', 'ROLE_ADMIN'],
        ]));
    }

    /**
     * @return string
     */
    protected function getInvalidCompanyResponse()
    {
        return base64_encode(serialize([
            'user' => [
                'id' => 'valor',
                'email' => 'valor',
                'username' => 'valor',
                'first_name' => 'valor',
                'last_name' => 'valor',
            ],
            'profile' => [
                'id' => 'valor',
                'name' => 'valor',
            ],
            'security_roles' => ['ROLE_SALES', 'ROLE_ADMIN'],
        ]));
    }

    /**
     * @return string
     */
    protected function getInvalidUserResponse()
    {
        return base64_encode(serialize([
            'company' => [
                'id' => 'valor',
                'name' => 'valor',
            ],
            'profile' => [
                'id' => 'valor',
                'name' => 'valor',
            ],
            'security_roles' => ['ROLE_SALES', 'ROLE_ADMIN'],
        ]));
    }


    /**
     * @return string
     */
    protected function getInvalidProfileResponse()
    {
        return base64_encode(serialize([
            'company' => [
                'id' => 'valor',
                'name' => 'valor',
            ],
            'user' => [
                'id' => 'valor',
                'email' => 'valor',
                'username' => 'valor',
                'first_name' => 'valor',
                'last_name' => 'valor',
            ],
            'security_roles' => ['ROLE_SALES', 'ROLE_ADMIN'],
        ]));
    }

}
