<?php 

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\ResponseEvent;

class CacheResponseListener
{
    public function onKernelResponse(ResponseEvent $event)
    {
        $response = $event->getResponse();
        $request = $event->getRequest();

        // Ne pas cacher les requêtes AJAX, les POST et l'administration
        if (!$request->isMethodCacheable() || str_starts_with($request->getRequestUri(), '/admin')) {
            return;
        }

        // Vérifie si la réponse est déjà mise en cache par un autre service
        if ($response->headers->has('Cache-Control')) {
            return;
        }

        // Appliquer un cache HTTP global de 1 heure
        $response->headers->set('Cache-Control', 'public, max-age=3600, s-maxage=3600');
        $response->setExpires(new \DateTime('+1 hour'));

        // Générer un ETag unique basé sur le contenu
        $etag = md5($response->getContent());
        $response->setETag($etag);

        // Vérifier si la réponse est déjà en cache côté client
        if ($response->isNotModified($request)) {
            $response->setNotModified();
        }

        $event->setResponse($response);
    }
}
