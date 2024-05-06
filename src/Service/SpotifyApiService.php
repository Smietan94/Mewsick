<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\SpotifyApiRequestDTO;
use App\Entity\Const\Constant;
use App\Enum\CatType;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class SpotifyApiService
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    /**
     * Generates url for request user authorization
     *
     * @return string
     */
    public function getAuthorizationUrl(): string
    {
        $uri   = 'https://accounts.spotify.com/pl/authorize?client_id=%s&redirect_uri=%s&response_type=code&scope=%s';
        $scope = implode('%20', Constant::SCOPE);

        return sprintf(
            $uri,
            $_ENV['SPOTIFY_API_CLIENT_ID'],
            Constant::REDIRECT_URI,
            $scope
        );
    }

    /**
     * Reqeusts spotify api access token
     *
     * @param  string  $authorization_code
     * @return void
     */
    public function getAccessToken(string $authorization_code): void
    {
        $headers = $this->getAccessTokenRequestHeaders();
        $body    = sprintf(
            'code=%s&redirect_uri=%s&grant_type=authorization_code',
            $authorization_code,
            Constant::REDIRECT_URI
        );

        $this->handleApiTokenRequest($headers, $body);
    }

    /**
     * Requests access token refresh
     *
     * @return void
     */
    public function refreshAccessToken(): void
    {
        $session = new Session();
        $headers = $this->getAccessTokenRequestHeaders();
        $body    = sprintf(
            'refresh_token=%s&grant_type=refresh_token',
            $session->get('REFRESH_TOKEN')
        );

        $this->handleApiTokenRequest($headers, $body);
    }

    /**
     * Checks if api access token is valid by making simple request
     *
     * @param  string $access_token
     * @return bool
     */
    public function checkIfAccessTokenIsValid(string $access_token): bool
    {
        try {
            $response = $this->client->request(
                'GET',
                sprintf('%s/artists/%s', Constant::SPOTIFY_API_URL, Constant::ARTIST_ID_FOR_TOKEN_VALIDATION),
                ['headers' => ['Authorization' => sprintf('Bearer %s', $access_token)]]
            );

            if ($response->getStatusCode() != 200) {
                return false;
            }

            return true;

        } catch (ClientException) {
            $this->refreshAccessToken();

            return true;
        }
    }

    /**
     * Process cat type choice to sample length which is use to set playlist lenght
     *
     * @param  Request $request
     * @param  array   $data
     * @return void
     */
    public function processCatTypeChoice(Request $request, array $data): void
    {
        $session      = $request->getSession();
        $catType      = (int) $data['elements']->value;
        $sampleLength = CatType::tryFrom($catType)->toSampleLength();

        $session->set('SAMPLE_LENGTH', $sampleLength);
        $session->set('CAT_TYPE', $catType);
    }

    /**
     * Creates new spotify playlis
     *
     * @param  Request $request
     * @param  string  $catName
     * @return void
     */
    public function createCatPlaylist(Request $request, string $catName)
    {
        $session     = $request->getSession();
        $accessToken = $session->get('ACCESS_TOKEN');
        $userId      = $session->get('API_USER_ID');
        $username    = $session->get('API_USER_NAME');

        $body = [
            'name'        => sprintf('Mewsick playlist by %s', $catName),
            'description' => sprintf('Playlist created by %s with %s in mind', $catName, $username),
            'public'      => true
        ];

        $requestData = new SpotifyApiRequestDTO(
            'POST',
            sprintf('%s/users/%s/playlists', Constant::SPOTIFY_API_URL, $userId),
            [
                'Authorization' => sprintf('Bearer %s', $accessToken),
                'Content-Type'  => 'application/json'
            ],
            json_encode($body)
        );

        $responseData = $this->processApiRequest($requestData);

        $session->set('CAT_PLAYLIST_ID', $responseData['id']);
    }

    /**
     * Returns array of random spotify playlists ids based on user choice
     *
     * @param  string $accessToken
     * @param  string $market
     * @param  string $query
     * @return string[]
     */
    public function getPlaylistsIds(string $accessToken, string $market, string $query = 'midwest emo'): array
    {
        $requestData = new SpotifyApiRequestDTO(
            'GET',
            sprintf('%s/search?q=q', Constant::SPOTIFY_API_URL),
            ['Authorization' => sprintf('Bearer %s', $accessToken)],
            query: [
                // TODO PROCESS DATA
                'q'      => $query,
                'type'   => 'playlist',
                'market' => $market,
                'offset' => 0,
                'limit'  => 15
            ]
        );

        $responseData = $this->processApiRequest($requestData);
        $playslists   = $responseData['playlists']['items'];
        $randomKeys   = array_rand($playslists, 3);

        return array_map(fn($key) => $playslists[$key]['id'], $randomKeys);
    }

    /**
     * Collects random tracks from provided playlist
     *
     * @param  string $playlistId
     * @param  string $accessToken
     * @param  string $market
     * @param  int    $sampleLength
     * @return array
     */
    public function getTracks(string $playlistId, string $accessToken, string $market, int $sampleLength): array
    {
        $requestData = new SpotifyApiRequestDTO(
            'GET',
            sprintf('%s/playlists/%s/tracks?market=%s', Constant::SPOTIFY_API_URL, $playlistId, $market),
            ['Authorization' => sprintf('Bearer %s', $accessToken)]
        );

        $responseData = $this->processApiRequest($requestData);
        $tracks       = $responseData['items'];
        $tracksKeys   = array_rand($tracks, $sampleLength);

        return array_map(fn($key) => $tracks[$key]['track']['uri'], $tracksKeys);
    }

    /**
     * Process user choice and adds tracks to created playlist
     *
     * @param  Request $request
     * @param  string  $data
     * @return void
     */
    public function addTracksToPlaylist(Request $request, string $query)
    {
        $session        = $request->getSession();
        $accessToken    = $session->get('ACCESS_TOKEN');
        $market         = $session->get('API_USER_COUNTRY');
        $catPlaylistId  = $session->get('CAT_PLAYLIST_ID');
        $sampleLenght   = $session->get('SAMPLE_LENGTH');
        $tracks         = [];

        $playlistsIds = $this->getPlaylistsIds($accessToken, $market, $query);

        foreach ($playlistsIds as $playlistId) {
            foreach ($this->getTracks($playlistId, $accessToken, $market, $sampleLenght) as $track) {
                array_push($tracks, $track);
            }
        }

        // TODO push tracks to catted playlist
        $this->updatePlaylistItems($accessToken, $catPlaylistId, $tracks);
    }

    /**
     * adds new tracks to cat's playlist
     *
     * @param  string $accessToken
     * @param  string $playlistId
     * @param  array  $tracks
     * @return void
     */
    public function updatePlaylistItems(string $accessToken, string $playlistId, array $tracks)
    {
        $body = [
            'uris'     => $tracks,
            'position' => 0
        ];

        $requestData = new SpotifyApiRequestDTO(
            'POST',
            sprintf('%s/playlists/%s/tracks', Constant::SPOTIFY_API_URL, $playlistId),
            [
                'Authorization' => sprintf('Bearer %s', $accessToken),
                'Content-Type'  => 'application/json'
            ],
            json_encode($body)
        );

        $this->processApiRequest($requestData);
    }

    /**
     * Adds current user data to session 
     *
     * @param  Request $request
     * @return void
     */
    public function processCurrentUserData(Request $request)
    {
        $session  = $request->getSession();

        $requestData = new SpotifyApiRequestDTO(
            'GET',
            sprintf('%s/me', Constant::SPOTIFY_API_URL),
            ['Authorization' => sprintf('Bearer %s', $session->get('ACCESS_TOKEN'))]
        );

        $responseData = $this->processApiRequest($requestData);

        $session->set('API_USER_COUNTRY', $responseData['country']);
        $session->set('API_USER_NAME', $responseData['display_name']);
        $session->set('API_USER_ID', $responseData['id']);
    }

    /**
     * Process api request
     *
     * @param  SpotifyApiRequestDTO $reqeustData
     * @return array
     */
    public function processApiRequest(SpotifyApiRequestDTO $reqeustData): array
    {
        // check response code
        $response = $this->client->request(
            $reqeustData->method,
            $reqeustData->url, [
            'headers' => $reqeustData->headers,
            'body'    => $reqeustData->body,
            'query'   => $reqeustData->query
        ]);

        return json_decode(
            $response->getBody()->getContents(),
            true
        );
    }

    /**
     * Get headers for access token request
     *
     * @return array
     */
    private function getAccessTokenRequestHeaders(): array
    {
        $key     = base64_encode(sprintf(
            '%s:%s',
            $_ENV['SPOTIFY_API_CLIENT_ID'],
            $_ENV['SPOTIFY_API_CLIENT_SECTRET']
        ));

        return [
            'Content-Type'  => Constant::CONTENT_TYPE,
            'Authorization' => sprintf('Basic %s', $key)
        ];
    }

    /**
     * Handles api access token request
     *
     * @param  array  $headers
     * @param  string $body
     * @return void
     */
    private function handleApiTokenRequest(array $headers, string $body)
    {
        $response = $this->client->request(
            'POST',
            Constant::ACCESS_TOKEN_URL, [
            'headers' => $headers,
            'body'    => $body
        ]);

        $this->processSessionValues($response);
    }

    /**
     * Adds api token access and refresh token values to session
     *
     * @param  mixed $response
     * @return void
     */
    private function processSessionValues(ResponseInterface $response)
    {
        $session = new Session();

        $responseData = json_decode(
            $response->getBody()->getContents(),
            true
        );

        $session->set('ACCESS_TOKEN', $responseData['access_token']);

        if (isset($responseData['refresh_token'])) {
            $session->set('REFRESH_TOKEN', $responseData['refresh_token']);
        }
    }
}