<?php
/*
 * This file is part of the Manuel Aguirre Project.
 *
 * (c) Manuel Aguirre <programador.manuel@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Optime\SimpleSsoClientBundle\EventListener;

use Optime\SimpleSsoClientBundle\Security\User\ExternalUserInterface;
use Optime\SimpleSsoClientBundle\Security\Authorization\ActiveSessionChecker;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CredentialsExpiredException;

/**
 * @author Manuel Aguirre <programador.manuel@gmail.com>
 */
class CheckActiveSessionListener
{
    const REDIRECT_CONTROLLER = 'Symfony\\Bundle\\FrameworkBundle\\Controller\\RedirectController::urlRedirectAction';
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;
    /**
     * @var ActiveSessionChecker
     */
    private $activeSessionChecker;
    /**
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * CheckActiveSessionListener constructor.
     * @param TokenStorageInterface $tokenStorage
     * @param ActiveSessionChecker $activeSessionChecker
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        ActiveSessionChecker $activeSessionChecker,
        LoggerInterface $logger = null
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->activeSessionChecker = $activeSessionChecker;
        $this->logger = $logger;
    }
    
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }
        
        if (!$token = $this->tokenStorage->getToken()) {
            return;
        }
        
        if (!$this->isExternalUser($token)) {
            // solo si es un external user, verificamos la sesión activa
            return;
        }
        
        if ($event->getRequest()->query->has('_sso_otp')) {
            //Si existe el parametro en la url, asumimos que estamos logueando y no verificamos la sesión.
            return;
        }
        
        if ($this->isSymfonyRedirection($event->getRequest())) {
            return;
        }
        
        if (null !== $this->logger) {
            $this->logger->info('Verificando si el usuario está aun logueado en el loyalty', [
                'username' => $token->getUsername(),
            ]);
        }
        
        if ($this->activeSessionChecker->verifySession($token)) {
            return;
        }
        
        if (null !== $this->logger) {
            $this->logger->info('El usuario ya no se encuentra logueado en el loyalty,' .
                ' por lo que se procede a expirar sus credenciales', [
                'username' => $token->getUsername(),
            ]);
        }
        
        throw new CredentialsExpiredException('El usuario yo no se encuentra logueado en el loyalty');
    }
    
    private function isSymfonyRedirection(Request $request)
    {
        if (!$request->attributes->has('_controller')) {
            return false;
        }
        
        return (trim($request->attributes->get('_controller'), '\\') === self::REDIRECT_CONTROLLER);
    }
    
    private function isExternalUser(TokenInterface $token)
    {
        return $token->getUser() instanceof ExternalUserInterface;
    }
}