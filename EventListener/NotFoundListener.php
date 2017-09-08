<?php
/**
 * Created by PhpStorm.
 * User: maxence
 * Date: 22/06/2017
 * Time: 07:52
 */

namespace Mdespeuilles\RedirectBundle\EventListener;
use Doctrine\ORM\EntityManager;
use Mdespeuilles\RedirectBundle\Entity\NotFound;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;

class NotFoundListener
{
    protected $em;
    protected $container;

    public function __construct(EntityManager $entityManager, ContainerInterface $container) {
        $this->em = $entityManager;
        $this->container = $container;
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        /*
        if (!$event->isMasterRequest()) {
            return;
        }*/

        $exception = $event->getException();
        if (!$exception instanceof HttpException || 404 !== (int) $exception->getStatusCode()) {
            return false;
        }

        $request = $event->getRequest();
        $requestedPath = $request->getPathInfo();
        $referer = $request->headers->get('referer');

        $notFound = $this->container->get('mdespeuilles.redirect.entity.not_found')->findOneBy([
            'path' => $requestedPath
        ]);

        if (!$notFound) {
            $notFound = new NotFound();
        }

        $notFound->setPath($requestedPath);
        $notFound->setReferer($referer);
        $notFound->setTimestamp(new \DateTime());

        $this->em->persist($notFound);
        $this->em->flush();

        $requestUri = str_replace('/app_dev.php', '', $request->getRequestUri());
        $redirect = $this->container->get('mdespeuilles.redirect.entity.redirect')->findOneBy([
            'source' => $requestUri
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