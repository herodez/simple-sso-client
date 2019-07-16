<?php

namespace SimpleSsoClientBundleTests\Security;

use Optime\SimpleSsoClientBundle\External\RemoteConnectionInterface;
use Optime\SimpleSsoClientBundle\Security\Exception\LoginException;
use Optime\SimpleSsoClientBundle\Security\SimpleSsoAuthenticator;
use Optime\SimpleSsoClientBundle\Security\User\Role\RolesResolverInterface;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\GuardAuthenticatorInterface;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;

/**
 * @author Manuel Aguirre <programador.manuel@gmail.com>
 */
class SimpleSsoAuthenticatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SimpleSsoAuthenticator
     */
    private $authenticator;
    private $remoteConnection;
    private $rolesResolver;

    public function setUp()
    {
        $this->remoteConnection = $this->prophesize(RemoteConnectionInterface::class);
        $this->rolesResolver = $this->prophesize(RolesResolverInterface::class);

        $this->authenticator = new SimpleSsoAuthenticator(
            $this->remoteConnection->reveal(),
            $this->rolesResolver->reveal()
        );
    }

    public function testImplementsInterface()
    {
        $this->assertInstanceOf(GuardAuthenticatorInterface::class, $this->authenticator);
    }

    public function testGetCreentials_NoSsoParameter()
    {
        $request = $this->prophesize(Request::class);
        $queryBag = $this->prophesize(ParameterBag::class);
        $queryBag->has('_sso_otp')->willReturn(false);

        $request->query = $queryBag->reveal();

        $result = $this->authenticator->getCredentials(
            $request->reveal()
        );

        $this->assertNull($result);
    }

    public function testGetCreentials_ReturnValues()
    {
        $otp = 'otp-value';

        $request = $this->prophesize(Request::class);
        $queryBag = $this->prophesize(ParameterBag::class);
        $queryBag->has('_sso_otp')->willReturn(true);
        $queryBag->get('_sso_otp')->willReturn($otp);

        $request->query = $queryBag->reveal();

        $result = $this->authenticator->getCredentials(
            $request->reveal()
        );

        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('otp', $result);
        $this->assertCount(1, $result);
        $this->assertSame($otp, $result['otp']);
    }

    public function testGetUser()
    {
        $user = $this->prophesize(UserInterface::class)->reveal();
        $provider = $this->prophesize(UserProviderInterface::class);
        $provider->loadUserByUsername('otp')->willReturn($user);

        $returnedUser = $this->authenticator->getUser(['otp' => 'otp'], $provider->reveal());

        $this->assertSame($user, $returnedUser);
    }

    public function testCheckCredentials()
    {
        $user = $this->prophesize(UserInterface::class)->reveal();

        $result = $this->authenticator->checkCredentials(null, $user);
        $this->assertTrue($result);

        $result = $this->authenticator->checkCredentials([], $user);
        $this->assertTrue($result);

        $result = $this->authenticator->checkCredentials(['otp' => 'otp'], $user);
        $this->assertTrue($result);
    }

    public function testOnAuthenticationSuccess()
    {
        $otp = 'otp-value';

        $request = $this->prophesize(Request::class);
        $queryBag = $this->prophesize(ParameterBag::class);
        $queryBag->get('_sso_otp')->willReturn($otp)->shouldBeCalled();
        $queryBag->all()->willReturn([])->shouldBeCalled();
        $queryBag->remove(Argument::cetera())->willReturn(null);

        $serverBag = $this->prophesize(ParameterBag::class);
        $serverBag->set('QUERY_STRING', Argument::any())->shouldBeCalled();

        $request->query = $queryBag->reveal();
        $request->server = $serverBag->reveal();
        $request->duplicate()->willReturn($request->reveal())->shouldBeCalled();
        $request->getUri()->willReturn('http://test.com')->shouldBeCalled();

        $response = $this->authenticator->onAuthenticationSuccess(
            $request->reveal(), $this->prophesize(TokenInterface::class)->reveal(), 'test'
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('http://test.com', $response->getTargetUrl());
    }

    public function testOnAuthenticationFailure()
    {
        $this->setExpectedException(LoginException::class);

        $request = $this->prophesize(Request::class);

        $this->authenticator->onAuthenticationFailure(
            $request->reveal(),
            $this->prophesize(AuthenticationException::class)->reveal()
        );
    }

    public function testStart_CorrectResponse()
    {
        $request = $this->prophesize(Request::class);

        $response = $this->authenticator->start($request->reveal());

        $this->assertInstanceOf(RedirectResponse::class, $response);
//        $this->assertSame('http://test.com', $response->getTargetUrl());
    }

    public function testStart_CorrectLoginUrlAndParameters()
    {
        $loginUrl = 'http://loyalty/sso/login';
        $username = 'Username-Test';
        $target = 'http://target-url';

        $this->remoteConnection->getLoginUrl()->willReturn($loginUrl);
        $this->remoteConnection->getUsername()->willReturn($username);
        $request = $this->prophesize(Request::class);
        $request->getUri()->willReturn($target);

        $response = $this->authenticator->start($request->reveal());

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $targetUrl = $response->getTargetUrl();

        $this->assertStringStartsWith($loginUrl, $targetUrl);
        $this->assertContains('username=Username-Test', $targetUrl);
        $this->assertContains(sprintf('_target=%s', urlencode($target)), $targetUrl);
    }

    public function testSupportsRememberMeMustReturnFalse()
    {
        $response = $this->authenticator->supportsRememberMe();
        $this->assertFalse($response);
    }

//    public function testCreateAuthenticatedToken_NoExternalUser()
//    {
//        $user = $this->prophesize(UserInterface::class);
//        $user->getRoles()
//            ->willReturn(['ROLE_USER'])
//            ->shouldBeCalled();
//
//        $token = $this->authenticator->createAuthenticatedToken(
//            $user->reveal(), 'test'
//        );
//
//        $this->assertInstanceOf(PostAuthenticationGuardToken::class, $token);
//        $this->assertSame($user->reveal(), $token->getUser());
//    }
//
//    public function testCreateAuthenticatedToken_ExternalUser_WithRolesByProfile()
//    {
//        $user = $this->prophesize(ExternalUserInterface::class)
//            ->willImplement(UserInterface::class);
//
//        $user->getRolesByProfile()
//            ->willReturn(['ROLE_USER'])
//            ->shouldBeCalled();
//
//        $token = $this->authenticator->createAuthenticatedToken(
//            $user->reveal(), 'test'
//        );
//
//        $this->assertInstanceOf(PostAuthenticationGuardToken::class, $token);
//        $this->assertSame($user->reveal(), $token->getUser());
//    }
//
//    public function testCreateAuthenticatedToken_ExternalUser_WithNOTRolesByProfile()
//    {
//        $this->authenticator = new SimpleSsoAuthenticator(
//            $this->remoteConnection->reveal(),
//            false //le indicamos que no debe cargar los roles por perfil
//        );
//
//        $user = $this->prophesize(ExternalUserInterface::class)
//            ->willImplement(UserInterface::class);
//
//        $user->getRoles()
//            ->willReturn(['ROLE_USER'])
//            ->shouldBeCalled();
//
//        $token = $this->authenticator->createAuthenticatedToken(
//            $user->reveal(), 'test'
//        );
//
//        $this->assertInstanceOf(PostAuthenticationGuardToken::class, $token);
//        $this->assertSame($user->reveal(), $token->getUser());
//    }
//  TODO: esto debe pasar a un test de RolesResolverInterface

    public function testStart_UnauthenticatedUser()
    {
        $request = $this->prophesize(Request::class);

        $loginUrl = '//test/';
        $username = 'testUser';
        $this->remoteConnection->getLoginUrl()->willReturn($loginUrl)->shouldBeCalled();
        $this->remoteConnection->getUsername()->willReturn($username)->shouldBeCalled();

        $response = $this->authenticator->start(
            $request->reveal(),
            $this->prophesize(AuthenticationException::class)->reveal()
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringStartsWith($loginUrl, $response->getTargetUrl());
        $this->assertContains($username, $response->getTargetUrl());
    }

    public function testStart_LoginException()
    {
        $request = $this->prophesize(Request::class);

        $this->remoteConnection->getLoginUrl()->shouldNotBeCalled();
        $this->remoteConnection->getUsername()->shouldNotBeCalled();

        $this->setExpectedException(HttpException::class);

        $this->authenticator->start(
            $request->reveal(),
            $this->prophesize(LoginException::class)->reveal()
        );
    }

    public function testCreateAuthenticatedToken()
    {
        $roles = ['ROLE_A', 'ROLE_B'];
        $user = $this->prophesize(UserInterface::class);

        $this->rolesResolver->getRoles($user->reveal())->willReturn($roles);

        $token = $this->authenticator->createAuthenticatedToken($user->reveal(), 'test');

        $this->assertInstanceOf(PostAuthenticationGuardToken::class, $token);
        $this->assertSame($user->reveal(), $token->getUser());
        $this->assertCount(2, $token->getRoles());

        foreach ($token->getRoles() as $key => $role) {
            $this->assertSame($roles[$key], $role->getRole());
        }
    }
}
