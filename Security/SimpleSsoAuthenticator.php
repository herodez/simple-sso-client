<?php

namespace Optime\SimpleSsoClientBundle\Security;

use Optime\SimpleSsoClientBundle\External\RemoteConnectionInterface;
use Optime\SimpleSsoClientBundle\Security\Exception\LoginException;
use Optime\SimpleSsoClientBundle\Security\Provider\SimpleSsoServerProvider;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class SimpleSsoAuthenticator extends AbstractGuardAuthenticator
{
    /**
     * @var RemoteConnectionInterface
     */
    private $remoteConnection;
    
    /**
     * @var UserProviderInterface
     */
    private $userProvider;
    
    /**
     * @var SimpleSsoServerProvider
     */
    private $serverProvider;
    
    /**
     * SimpleSsoAuthenticator constructor.
     * @param RemoteConnectionInterface $remoteConnection
     * @param UserProviderInterface $userProvider
     * @param SimpleSsoServerProvider $serverProvider
     */
    public function __construct(
        RemoteConnectionInterface $remoteConnection,
        UserProviderInterface $userProvider,
        SimpleSsoServerProvider $serverProvider
    ) {
        $this->remoteConnection = $remoteConnection;
        $this->userProvider = $userProvider;
        $this->serverProvider = $serverProvider;
    }
    
    /**
     * @param Request $request
     * @return array|void
     */
    public function getCredentials(Request $request)
    {
        return [
            'otp' => $request->query->get('_sso_otp')
        ];
    }
    
    /**
     * @param mixed $credentials
     * @param UserProviderInterface $userProvider
     * @return UserInterface
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        return $this->userProvider->loadUserByUsername($credentials['otp']);
    }
    
    /**
     * @param mixed $credentials
     * @param UserInterface $user
     * @return bool
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        return true;
    }
    
    /**
     * @param Request $request
     * @param TokenInterface $token
     * @param string $providerKey
     * @return RedirectResponse|Response|null
     * @throws Exception\NotSsoServerExits
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $request = $request->duplicate();
        $token->setAttribute('_sso_otp', $request->query->get('_sso_otp'));
        $token->setAttribute('_sso_server_id', $this->serverProvider->getCurrentServer()->getServerId());
        $request->query->remove('_sso_otp');
        $request->query->remove('_sso_server_id');
        $request->server->set('QUERY_STRING', http_build_query($request->query->all()));
        
        return new RedirectResponse($request->getUri(), 302);
    }
    
    /**
     * @param Request $request
     * @param AuthenticationException $exception
     * @return Response|void|null
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        throw new LoginException('Error de autenticación', $exception->getCode(), $exception);
    }
    
    /**
     * @param Request $request
     * @param AuthenticationException|null $authException
     * @return RedirectResponse|Response
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        if ($authException instanceof LoginException) {
            // Si el AuthenticationFailure lanza un LoginException, no queremos redirigir al server sso.
            throw  new HttpException(
                Response::HTTP_UNAUTHORIZED,
                $authException->getMessage(),
                $authException->getPrevious()
            );
        }
        
        $url = sprintf(
            '%s?username=%s&_target=%s',
            $this->remoteConnection->getLoginUrl(),
            $this->remoteConnection->getUsername(),
            urlencode($request->getUri())
        );
        
        return new RedirectResponse($url);
    }
    
    /**
     * @return bool
     */
    public function supportsRememberMe()
    {
        return false;
    }
    
    /**
     * Does the authenticator support the given Request?
     *
     * If this returns false, the authenticator will be skipped.
     *
     * @param Request $request
     *
     * @return bool
     */
    public function supports(Request $request)
    {
        if ($request->query->has('_sso_otp')) {
            return true;
        }
        
        return false;
    }
}