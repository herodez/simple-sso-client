<?php

namespace Optime\SimpleSsoClientBundle\Tests\Security\Authorization;

use Optime\SimpleSsoClientBundle\External\RemoteConnectionInterface;
use Optime\SimpleSsoClientBundle\Security\Authorization\ActiveSessionChecker;
use Optime\SimpleSsoClientBundle\Security\Authorization\RemoteConnectionVerifier;
use Prophecy\Argument;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @author Manuel Aguirre <programador.manuel@gmail.com>
 */
class ActiveSessionCheckerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Optime\SimpleSsoClientBundle\Security\Authorization\ActiveSessionChecker
     */
    private $checker;
    private $remoteVerifier;
    private $remoteConnection;

    public function setUp()
    {
        $this->remoteVerifier = $this->prophesize(RemoteConnectionVerifier::class);
        $this->remoteVerifier->verify(Argument::any(), Argument::any(), Argument::any())->willReturn(false);
        $this->remoteVerifier->verify('must-return-true', Argument::any(), Argument::any())->willReturn(true);

        $this->remoteConnection = $this->prophesize(RemoteConnectionInterface::class);

        $this->checker = new ActiveSessionChecker(
            $this->remoteVerifier->reveal(),
            $this->remoteConnection->reveal()
        );
    }

    public function testReturnTrueIfRemoteConnectionReturnTrue()
    {
        $token = $this->prophesize(TokenInterface::class);
        $token->getAttribute('_sso_otp')->willReturn('must-return-true');

        $return = $this->checker->verifySession($token->reveal());

        $this->assertTrue($return);
    }

    public function testReturnFalseIfTokenNotHaveOTPAttribute()
    {
        $token = $this->prophesize(TokenInterface::class);
        $token->getAttribute('_sso_otp')->willThrow(\InvalidArgumentException::class);

        $return = $this->checker->verifySession($token->reveal());

        $this->assertFalse($return);
    }

    public function testReturnFalseIfTokenOTPAttributeIsNull()
    {
        $token = $this->prophesize(TokenInterface::class);
        $token->getAttribute('_sso_otp')->willReturn(null);

        $return = $this->checker->verifySession($token->reveal());

        $this->assertFalse($return);
    }

    public function testReturnFalseIfRemoteCallReturnFalse()
    {
        $token = $this->prophesize(TokenInterface::class);
        $token->getAttribute('_sso_otp')->willReturn('must-return-false');

        $return = $this->checker->verifySession($token->reveal());

        $this->assertFalse($return);
    }

    public function testPasswordAlgorithm()
    {
        $otp = 'clave-otp';
        $privateKey = 'private-key-test';
        $created = null;
        $password = null;
        $expectedPassword = null;

        $token = $this->prophesize(TokenInterface::class);
        $token->getAttribute('_sso_otp')->willReturn($otp);

        $this->remoteConnection
            ->getPrivateKey()
            ->willReturn($privateKey);

        $this->remoteVerifier
            ->verify($otp, Argument::any(), Argument::any())
            ->will(function($a) use (&$expectedPassword, &$password, $privateKey){
                // generamos manualmente el password, para compararlo con el password
                // generado por el método verifySession del Checker.
                $expectedPassword = base64_encode(sha1($a[0]. $a[1] . $privateKey));
                // almacenamos el password generado por el checker.
                $password = $a[2];

                return true;
            })
            ->shouldBeCalled();

        $this->checker->verifySession($token->reveal());

        $this->assertSame($expectedPassword, $password);
    }
}