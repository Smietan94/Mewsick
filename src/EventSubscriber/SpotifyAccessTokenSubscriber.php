<?php

namespace App\EventSubscriber;

use App\Service\SpotifyApiService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SpotifyAccessTokenSubscriber implements EventSubscriberInterface
{
    private string $authorization_url;

    public function __construct(
        private SpotifyApiService $spotifyApiService
    ) {
        $this->authorization_url = $this->spotifyApiService->getAuthorizationUrl();
    }

    public function onKernelController(ControllerEvent $event)
    {
        $session      = $event->getRequest()->getSession();
        $access_token = $session->get('ACCESS_TOKEN');

        if (!$access_token || !$this->spotifyApiService->checkIfAccessTokenIsValid($access_token)) {
            $response = new RedirectResponse(
                $this->authorization_url
            );

            return $response->send();
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }
}
