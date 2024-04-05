<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Const\RouteName;
use App\Entity\Const\RoutePath;
use App\Form\ChooseFighterType;
use App\Form\PlaylistCatNameType;
use App\Service\SpotifyApiService;
use GuzzleHttp\Exception\ClientException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: RoutePath::BASE_SPOTIFY_API_CONTROLLER)]
class SpotifyApiController extends AbstractController
{
    public function __construct(
        private SpotifyApiService $spotifyApiService
    ) {
    }

    #[Route(path: RoutePath::START, name: RouteName::APP_START)]
    public function index(Request $request): Response
    {
        try {
            $authorization_code = $request->get('code');

            if ($authorization_code) {
                $this->spotifyApiService->getAccessToken($authorization_code);

                return $this->redirectToRoute(RouteName::APP_START);
            }

            if (!$request->getSession()->get('API_USER_ID')) {
                $this->spotifyApiService->processCurrentUserData($request);
            }

            return $this->render('start.html.twig', [
                'controller_name' => $this::class,
            ]);

        } catch (ClientException) {
            return $this->render('start.html.twig', [
                'controller_name' => $this::class,
            ]);
        }
    }

    #[Route(path: RoutePath::CREATE_SPOTIFY_PLAYLIST, name: RouteName::APP_CREATE_SPOTIFY_PLAYLIST)]
    public function createSpotifyPlaylist(Request $request): Response
    {
        $form = $this->createForm(PlaylistCatNameType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            // TODO create service method which will create new playlist

            return $this->redirectToRoute(RouteName::APP_CHOOSE_YOUR_FIGHTER);
        }

        return $this->render('playlistCatNameForm.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: RoutePath::CHOOSE_YOUR_FIGHTER, name: RouteName::APP_CHOOSE_YOUR_FIGHTER)]
    public function chooseYourCat(Request $request): Response
    {
        // TODO create form with cat type
        $form = $this->createForm(ChooseFighterType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $this->spotifyApiService->addTracksToPlaylist($request, $data);
        }

        return $this->render('choseYourFighter.html.twig', [
            'form' => $form
        ]);
    }

    #[Route(path: RoutePath::CHOOSE_CAT_COLOR, name: RouteName::APP_CHOOSE_CAT_COLOR)]
    public function chooseCatColor(Request $request): Response
    {
        return $this->render('');
    }

    #[Route(path: RoutePath::CHOOSE_CAT_HAT, name: RouteName::APP_CHOOSE_CAT_HAT)]
    public function chooseCatHat(Request $request): Response
    {
        return $this->render('');
    }

    #[Route(path: RoutePath::CHOOSE_CAT_HOBBY, name: RouteName::APP_CHOOSE_CAT_HOBBY)]
    public function chooseCatHobby(Request $request): Response
    {
        return $this->render('');
    }

    #[Route(path: RoutePath::CHOOSE_CAT_BACKGROUND, name: RouteName::APP_CHOOSE_CAT_BACKGROUND)]
    public function chooseCatBackground(Request $request): Response
    {
        return $this->render('');
    }

    #[Route(path: RoutePath::CAT_REVEAL, name: RouteName::APP_CAT_REVEAL)]
    public function catReveal(Request $request): Response
    {
        return $this->render('');
    }

    #[Route(path: RoutePath::CATS_GALLERY, name: RouteName::APP_CATS_GALLERY)]
    public function catsGallery(Request $request): Response
    {
        return $this->render('');
    }
}
