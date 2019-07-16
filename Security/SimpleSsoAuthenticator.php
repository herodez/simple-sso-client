<?php

namespace Optime\SimpleSsoClientBundle\Security;

use Optime\SimpleSsoClientBundle\External\RemoteConnectionInterface;
use Optime\SimpleSsoClientBundle\Security\Exception\LoginException;
use Optime\SimpleSsoClientBundle\Security\User\Role\RolesResolverInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Http\HttpUtils;

class SimpleSsoAuthenticator extends AbstractGuardAuthenticator
{
    /**
     * @var RemoteConnectionInterface
     */
    private $remoteConnection;
    
    /**
     * @var RolesResolverInterface
     */
    private $rolesResolver;
    
    /**
     * @var string
     */
    private $loginFormPath;
    
    /**
     * @var UserProviderInterface 
     */
    private $userProvider;
    
    /**
     * @var HttpUtils 
     */
    private $httpUtils;
    
    /**
     * @var boolean 
     */
    private $hasDefaultSsoServer;
    
    /**
     * SimpleSsoAuthenticator constructor.
     * @param RemoteConnectionInterface $remoteConnection
     * @param \Twig_Environment $twig
     * @param $rolesFromProfile
     */
    public function __construct(
        RemoteConnectionInterface $remoteConnection,
        UserProviderInterface $userProvider,
        HttpUtils $httpUtils,
        RolesResolverInterface $rolesResolver,
        $loginFormPath,
        $hasDefaultSsoServer
    ) {
        $this->remoteConnection = $remoteConnection;
        $this->rolesResolver = $rolesResolver;
        $this->loginFormPath = $loginFormPath;
        $this->userProvider = $userProvider;
        $this->httpUtils = $httpUtils;
        $this->hasDefaultSsoServer = $hasDefaultSsoServer;
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
    
    public function checkCredentials($credentials, UserInterface $user)
    {
        return true;
    }
    
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $request = $request->duplicate();
        $token->setAttribute('_sso_otp', $request->query->get('_sso_otp'));
        $token->setAttribute('_sso_server_id', $request->query->get('_sso_server_id'));
        $request->query->remove('_sso_otp');
        $request->query->remove('_sso_login');
        $request->query->remove('_sso_server_id');
        $request->server->set('QUERY_STRING', http_build_query($request->query->all()));
        
        return new RedirectResponse($request->getUri(), 302);
    }
    
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        throw new LoginException('Error de autenticación', $exception->getCode(), $exception);
    }
    
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
        
        // If not request for SSO login was make.
        if ($this->hasDefaultSsoServer === false && !($request->query->has('_sso_login') && $request->query->has('_sso_server_id'))
        ) {
            if ($this->loginFormPath === null) {
                throw  new HttpException(
                    Response::HTTP_UNAUTHORIZED
                );
            } else {
                return $this->httpUtils->createRedirectResponse($request, $this->loginFormPath);
            }
        }
        
        $url = sprintf(
            '%s?username=%s&_target=%s',
            $this->remoteConnection->getLoginUrl(),
            $this->remoteConnection->getUsername(),
            urlencode($request->getUri())
        );
        
        return new RedirectResponse($url);
    }
    
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