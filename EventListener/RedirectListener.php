<?php
/**
 * Created by PhpStorm.
 * User: maxence
 * Date: 22/06/2017
 * Time: 09:42
 */

namespace Mdespeuilles\RedirectBundle\EventListener;


use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class RedirectListener
{
    protected $em;
    protected $container;

    public function __construct(EntityManager $entityManager, ContainerInterface $container) {
        $this->em = $entityManager;
        $this->container = $container;
    }


    public function onKernelRequest(GetResponseEvent $event) {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();
        $requestedPath = $request->getPathInfo();

        $redirect = $this->container->get('mdespeuilles.redirect.entity.redirect')->findOneBy([
            'source' => $requestedPath
        ]);

        if (!$redirect) {
            $redirect = $this->container->get('mdespeuilles.redirect.entity.redirect')->findOneBy([
                'source' => $requestedPath
            ]);
        }

        if ($redirect) {
            $status = 302;
            if ($redirect->getPermanent()) {
                $status = 301;
            }
            $event->setResponse(new RedirectResponse($redirect->getDestination(), $status));
        }
    }
}