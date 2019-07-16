<?php

namespace Optime\SimpleSsoClientBundle\Tests\EventListener;

use Optime\SimpleSsoClientBundle\EventListener\CheckActiveSessionListener;
use Optime\SimpleSsoClientBundle\ExternalUserInterface;
use Optime\SimpleSsoClientBundle\Security\Authorization\ActiveSessionChecker;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CredentialsExpiredException;

/**
 * @author Manuel Aguirre <programador.manuel@gmail.com>
 */
class CheckActiveSessionListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CheckActiveSessionListener
     */
    private $listener;
    private $tokenStorage;
    private $activeSessionChecker;

    public function setUp()
    {
        $this->tokenStorage = $this->prophesize(TokenStorageInterface::class);
        $this->activeSessionChecker = $this->prophesize(ActiveSessionChecker::class);

        $this->listener = new CheckActiveSessionListener(
            $this->tokenStorage->reveal(),
            $this->activeSessionChecker->reveal()
        );
    }

    public function testOnKernelRequest_NoMasterRequest()
    {
        $event = $this->prophesize(GetResponseEvent::class);
        $event->isMasterRequest()->willReturn(false)->shouldBeCalled();
        $this->tokenStorage->getToken()->shouldNotBeCalled();
        $this->activeSessionChecker->verifySession(Argument::any())->shouldNotBeCalled();

        $this->listener->onKernelRequest($event->reveal());
    }

    public function testOnKernelRequest_NoToken()
    {
        $event = $this->prophesize(GetResponseEvent::class);
        $event->isMasterRequest()->willReturn(true)->shouldBeCalled();
        $this->tokenStorage->getToken()->willReturn(null)->shouldBeCalled();
        $this->activeSessionChecker->verifySession(Argument::any())->shouldNotBeCalled();

        $this->listener->onKernelRequest($event->reveal());
    }

    public function testOnKernelRequest_CheckIfRequestHasSsoToken_CaseTrue()
    {
        $token = $this->prophesize(TokenInterface::class);
        $token->getUser()->shouldBeCalled()
            ->willReturn($this->prophesize(ExternalUserInterface::class)->reveal());

        $event = $this->prophesize(GetResponseEvent::class);
        $event->isMasterRequest()->willReturn(true)->shouldBeCalled();
        $this->tokenStorage->getToken()->willReturn($token->reveal())->shouldBeCalled();
        $this->activeSessionChecker->verifySession(Argument::any())->shouldNotBeCalled();

        $request = $this->prophesize(Request::class);
        $event->getRequest()->willReturn($request->reveal())->shouldBeCalled();
        $queryBag = $this->createRequestQueryAttributesMock($request);
        $attributesBag = $this->createRequestAttributesMock($request);
        $queryBag->has('_sso_otp')->shouldBeCalled()->willReturn(true);

        $this->listener->onKernelRequest($event->reveal());
    }

    public function testOnKernelRequest_MasterRequestAndToken_NotSsoInQuery()
    {
        $token = $this->prophesize(TokenInterface::class);
        $token->getUser()->shouldBeCalled()
            ->willReturn($this->prophesize(ExternalUserInterface::class)->reveal());

        $event = $this->prophesize(GetResponseEvent::class);
        $event->isMasterRequest()->willReturn(true)->shouldBeCalled();
        $event->getRequest()->willReturn(null);

        $this->tokenStorage->getToken()->willReturn($token->reveal());
        $this->activeSessionChecker->verifySession($token->reveal())
            ->willReturn(true)
            ->shouldBeCalled();

        $request = $this->prophesize(Request::class);
        $event->getRequest()->willReturn($request->reveal())->shouldBeCalled();
        $queryBag = $this->createRequestQueryAttributesMock($request);
        $attributesBag = $this->createRequestAttributesMock($request);
        $queryBag->has('_sso_otp')->shouldBeCalled()->willReturn(false);

        $this->listener->onKernelRequest($event->reveal());
    }

    public function testOnKernelRequest_ThrowExceptionOnInvalidSessionVerification()
    {
        $this->setExpectedExceptionRegExp(CredentialsExpiredException::class, '/loyalty/');

        $token = $this->prophesize(TokenInterface::class);
        $token->getUser()->shouldBeCalled()
            ->willReturn($this->prophesize(ExternalUserInterface::class)->reveal());

        $event = $this->prophesize(GetResponseEvent::class);
        $event->isMasterRequest()->willReturn(true)->shouldBeCalled();
        $event->getRequest()->shouldBeCalled();

        $request = $this->prophesize(Request::class);
        $event->getRequest()->willReturn($request->reveal())->shouldBeCalled();
        $queryBag = $this->createRequestQueryAttributesMock($request);
        $attributesBag = $this->createRequestAttributesMock($request);
        $queryBag->has('_sso_otp')->shouldBeCalled()->willReturn(false);

        $this->tokenStorage->getToken()->willReturn($token->reveal());
        $this->activeSessionChecker->verifySession($token->reveal())->willReturn(false)->shouldBeCalled();

        $this->listener->onKernelRequest($event->reveal());
    }

    public function testOnKernelRequest_CheckIfControllerIsRedirection_CaseTrue()
    {
        $token = $this->prophesize(TokenInterface::class);
        $token->getUser()->shouldBeCalled()
            ->willReturn($this->prophesize(ExternalUserInterface::class)->reveal());

        $event = $this->prophesize(GetResponseEvent::class);
        $event->isMasterRequest()->willReturn(true);
        $this->tokenStorage->getToken()->willReturn($token->reveal());

        $request = $this->prophesize(Request::class);
        $event->getRequest()->willReturn($request->reveal());
        $queryBag = $this->createRequestQueryAttributesMock($request);
        $attributesBag = $this->createRequestAttributesMock($request);
        $queryBag->has('_sso_otp')->willReturn(false);

        $attributesBag->has('_controller')->shouldBeCalled()->willReturn(true);
        $attributesBag->get('_controller')
            ->shouldBeCalled()
            ->willReturn('Symfony\\Bundle\\FrameworkBundle\\Controller\\RedirectController::urlRedirectAction');

        $this->activeSessionChecker->verifySession(Argument::any())->shouldNotBeCalled();

        $this->listener->onKernelRequest($event->reveal());
    }

    public function testOnKernelRequest_CheckIfControllerIsRedirection_CaseFalse_NoController_Attribute()
    {
        $token = $this->prophesize(TokenInterface::class);
        $token->getUser()->shouldBeCalled()
            ->willReturn($this->prophesize(ExternalUserInterface::class)->reveal());

        $event = $this->prophesize(GetResponseEvent::class);
        $event->isMasterRequest()->willReturn(true);
        $this->tokenStorage->getToken()->willReturn($token->reveal());

        $request = $this->prophesize(Request::class);
        $event->getRequest()->willReturn($request->reveal());
        $queryBag = $this->createRequestQueryAttributesMock($request);
        $attributesBag = $this->createRequestAttributesMock($request);
        $queryBag->has('_sso_otp')->willReturn(false);

        $attributesBag->has('_controller')->shouldBeCalled()->willReturn(false);

        $this->activeSessionChecker->verifySession(Argument::any())
            ->shouldBeCalled()
            ->willReturn(true);

        $this->listener->onKernelRequest($event->reveal());
    }

    public function testOnKernelRequest_CheckIfControllerIsRedirection_CaseFalse_OtherController()
    {
        $token = $this->prophesize(TokenInterface::class);
        $token->getUser()->shouldBeCalled()
            ->willReturn($this->prophesize(ExternalUserInterface::class)->reveal());

        $event = $this->prophesize(GetResponseEvent::class);
        $event->isMasterRequest()->willReturn(true);
        $this->tokenStorage->getToken()->willReturn($token->reveal());

        $request = $this->prophesize(Request::class);
        $event->getRequest()->willReturn($request->reveal());
        $queryBag = $this->createRequestQueryAttributesMock($request);
        $attributesBag = $this->createRequestAttributesMock($request);
        $queryBag->has('_sso_otp')->willReturn(false);

        $attributesBag->has('_controller')->shouldBeCalled()->willReturn(true);
        $attributesBag->get('_controller')
            ->shouldBeCalled()
            ->willReturn('Bundle\\Controller::action');

        $this->activeSessionChecker->verifySession(Argument::any())
            ->shouldBeCalled()
            ->willReturn(true);

        $this->listener->onKernelRequest($event->reveal());
    }

    public function testOnKernelRequest_ExternalUserInstance_CaseTrue()
    {
        $token = $this->prophesize(TokenInterface::class);
        $token->getUser()->shouldBeCalled()
            ->willReturn($this->prophesize(ExternalUserInterface::class)->reveal());

        $event = $this->prophesize(GetResponseEvent::class);
        $event->isMasterRequest()->willReturn(true);
        $this->tokenStorage->getToken()->willReturn($token->reveal());

        $request = $this->prophesize(Request::class);
        $event->getRequest()->willReturn($request->reveal());
        $queryBag = $this->createRequestQueryAttributesMock($request);
        $this->createRequestAttributesMock($request);
        $queryBag->has('_sso_otp')->willReturn(false);

//        $attributesBag->has('_controller')->willReturn(true);
//        $attributesBag->get('_controller')->willReturn('Bundle\\Controller::action');

        $this->activeSessionChecker->verifySession(Argument::any())
            ->shouldBeCalled()
            ->willReturn(true);

        $this->listener->onKernelRequest($event->reveal());
    }

    public function testOnKernelRequest_ExternalUserInstance_CaseFalse()
    {
        $token = $this->prophesize(TokenInterface::class);
        $token->getUser()->shouldBeCalled()
            ->willReturn(new \stdClass());

        $event = $this->prophesize(GetResponseEvent::class);
        $event->isMasterRequest()->willReturn(true);
        $this->tokenStorage->getToken()->willReturn($token->reveal());

        $request = $this->prophesize(Request::class);
        $event->getRequest()->willReturn($request->reveal());
        $queryBag = $this->createRequestQueryAttributesMock($request);
        $this->createRequestAttributesMock($request);
        $queryBag->has('_sso_otp')->willReturn(false);

//        $attributesBag->has('_controller')->willReturn(true);
//        $attributesBag->get('_controller')->willReturn('Bundle\\Controller::action');

        $this->activeSessionChecker->verifySession(Argument::any())
            ->shouldNotBeCalled();

        $this->listener->onKernelRequest($event->reveal());
    }

    /**
     * @param $request
     * @return \Prophecy\Prophecy\ObjectProphecy|ParameterBag
     */
    protected function createRequestAttributesMock($request)
    {
        $attributesBag = $this->prophesize(ParameterBag::class);
        $request->attributes = $attributesBag->reveal();

        return $attributesBag;
    }

    /**
     * @param $request
     * @return \Prophecy\Prophecy\ObjectProphecy|ParameterBag
     */
    protected function createRequestQueryAttributesMock($request)
    {
        $queryBag = $this->prophesize(ParameterBag::class);
        $request->query = $queryBag->reveal();

        return $queryBag;
    }
}
