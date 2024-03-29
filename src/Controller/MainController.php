<?php

namespace App\Controller;

use App\Service\SpotifyApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MainController extends AbstractController
{
    public function __construct(
        private SpotifyApiService $spotifyApiService
    ) {
    }

    #[Route('/main', name: 'app_main')]
    public function index(Request $request): Response
    {
        $authorization_code = $request->get('code');

        if ($authorization_code) {
            $this->spotifyApiService->getAccessToken($authorization_code);
        }

        return $this->render('main/index.html.twig', [
            'controller_name' => 'MainController',
        ]);
    }
}
