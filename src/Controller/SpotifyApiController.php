<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Const\RouteName;
use App\Entity\Const\RoutePath;
use App\Enum\CatBackground;
use App\Enum\CatColor;
use App\Enum\CatHat;
use App\Enum\CatType;
use App\Form\PlaylistCatNameType;
use App\Service\DataService;
use App\Service\FileService;
use App\Service\FormService;
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
        private SpotifyApiService $spotifyApiService,
        private FormService       $formService,
        private DataService       $dataService,
        private FileService       $fileService
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
            // dd($data);
            // TODO create service method which will create new playlist
            $this->spotifyApiService->createCatPlaylist($request, $data['cat_name']);

            return $this->redirectToRoute(RouteName::APP_CHOOSE_YOUR_FIGHTER);
        }

        return $this->render('playlistCatNameForm.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: RoutePath::CHOOSE_YOUR_FIGHTER, name: RouteName::APP_CHOOSE_YOUR_FIGHTER)]
    public function chooseYourCat(Request $request): Response
    {
        $form = $this->formService->createElementChoiceForm(CatType::cases());

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $this->spotifyApiService->processCatTypeChoice($request, $data);

            return $this->redirectToRoute(RouteName::APP_CHOOSE_CAT_COLOR);
        }

        return $this->render('chooseYourFighter.html.twig', [
            'pill_text' => 'choose your fighter',
            'subject'   => 'type',
            'form'      => $form
        ]);
    }

    #[Route(path: RoutePath::CHOOSE_CAT_COLOR, name: RouteName::APP_CHOOSE_CAT_COLOR)]
    public function chooseCatColor(Request $request): Response
    {
        $form = $this->formService->createElementChoiceForm(CatColor::cases());

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data  = $form->getData();
            $color = $this->dataService->getCatElementValue($data);

            $request->getSession()->set('CAT_COLOR', $color);

            $this->spotifyApiService->addTracksToPlaylist($request, CatColor::tryFrom($color)->toQuery());
            return $this->redirectToRoute(RouteName::APP_CHOOSE_CAT_HAT);
        }

        return $this->render('elementSelection.html.twig', [
            'subject' => 'color',
            'form'    => $form,
        ]);
    }

    #[Route(path: RoutePath::CHOOSE_CAT_HAT, name: RouteName::APP_CHOOSE_CAT_HAT)]
    public function chooseCatHat(Request $request): Response
    {
        $form = $this->formService->createElementChoiceForm(CatHat::cases());

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $hat  = $this->dataService->getCatElementValue($data);

            $request->getSession()->set('CAT_HAT', $hat);

            return $this->redirectToRoute(RouteName::APP_CHOOSE_CAT_BACKGROUND);
        }

        return $this->render('elementSelection.html.twig', [
            'subject' => 'hat',
            'form'    => $form,
        ]);
    }

    #[Route(path: RoutePath::CHOOSE_CAT_BAG, name: RouteName::APP_CHOOSE_CAT_BAG)]
    public function chooseCatBag(Request $request): Response
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
        $form = $this->formService->createElementChoiceForm(CatBackground::cases());

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data       = $form->getData();
            $background = $this->dataService->getCatElementValue($data);

            $request->getSession()->set('CAT_BACKGROUND', $background);

            $this->fileService->combineLayers($request);
            $this->spotifyApiService->updatePlaylistCoverPhoto($request);

            return $this->redirectToRoute(RouteName::APP_CAT_REVEAL);
        }

        return $this->render('elementSelection.html.twig', [
            'subject' => 'background',
            'form'    => $form
        ]);
    }

    #[Route(path: RoutePath::CAT_REVEAL, name: RouteName::APP_CAT_REVEAL)]
    public function catReveal(Request $request): Response
    {
        $session      = $request->getSession();
        $catName      = $session->get('CAT_NAME');
        $catPhotoName = $session->get('CAT_PHOTO_NAME');

        return $this->render('catReveal.html.twig', [
            'pill_text'      => sprintf('This is %s!', $catName),
            'cat_name'       => $catName,
            'cat_photo_path' => sprintf('images/cats/playlist_cover_download/%s.jpg', $catPhotoName),
            'playlist_url'   => $this->spotifyApiService->getPlaylistUrl($request)
        ]);
    }

    #[Route(path: RoutePath::CATS_GALLERY, name: RouteName::APP_CATS_GALLERY)]
    public function catsGallery(Request $request): Response
    {
        $catsPlaylists = $this->spotifyApiService->getMewsickPlaylists($request);

        return $this->render('catsGallery.html.twig', [
            'pill_text' => 'Cats Gallery',
            'playlists' => $catsPlaylists
        ]);
    }
}
