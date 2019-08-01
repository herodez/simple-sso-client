<?php


namespace Optime\SimpleSsoClientBundle\EventListener;


use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Routing\RequestContextAwareInterface;

class ServerIdListener
{
    /**
     * @var RequestContextAwareInterface
     */
    private $contextAware;
    
    public function __construct(RequestContextAwareInterface $contextAware)
    {
        $this->contextAware = $contextAware;
    }
    
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->getRequest()->attributes->has('_sso_server_id')) {
            return;
        }
        
        $this->contextAware->getContext()->setParameter('_sso_server_id',
            $event->getRequest()->attributes->get('_sso_server_id'));
    }
}