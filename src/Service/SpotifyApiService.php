<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\SpotifyApiRequestDTO;
use App\DTO\SpotifyPlaylistDTO;
use App\Entity\Const\Constant;
use App\Enum\CatHat;
use App\Enum\CatType;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Session\Session;

class SpotifyApiService
{
    private Client  $client;
    private Session $session;

    public function __construct(
        private string      $CLIENT_ID,
        private string      $CLIENT_SECRET,
        private FileService $fileService,
    ) {
        $this->client  = new Client();
        $this->session = new Session();
    }

    /**
     * Generates url for request user authorization
     *
     * @return string
     */
    public function getAuthorizationUrl(): string
    {
        $scope = implode('%20', Constant::SCOPE);

        return sprintf(
            Constant::AUTHORIZATION_URL,
            $this->CLIENT_ID,
            Constant::REDIRECT_URI,
            $scope
        );
    }

    /**
     * Reqeusts spotify api access token
     *
     * @param  string $authorization_code
     * @return void
     */
    public function getAccessToken(string $authorization_code): void
    {
        $body = sprintf(
            'code=%s&redirect_uri=%s&grant_type=authorization_code',
            $authorization_code,
            Constant::REDIRECT_URI
        );

        $this->handleApiTokenRequest($body);
    }

    /**
     * Requests access token refresh
     *
     * @return void
     */
    public function refreshAccessToken(): void
    {
        $body = sprintf(
            'refresh_token=%s&grant_type=refresh_token',
            $this->session->get('REFRESH_TOKEN')
        );

        $this->handleApiTokenRequest($body);
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
     * @param  array $data
     * @return void
     */
    public function processCatTypeChoice(array $data): void
    {
        $catType      = (int) $data['elements']->value;
        $sampleLength = CatType::tryFrom($catType)->toSampleLength();

        $this->session->set('SAMPLE_LENGTH', $sampleLength);
        $this->session->set('CAT_TYPE', $catType);
    }

    /**
     * Creates new spotify playlis
     *
     * @param  string $catName
     * @return void
     */
    public function createCatPlaylist(string $catName): void
    {
        $accessToken = $this->session->get('ACCESS_TOKEN');
        $userId      = $this->session->get('API_USER_ID');
        $username    = $this->session->get('API_USER_NAME');

        $body = [
            'name'        => sprintf('%s %s', Constant::SPOTIFY_PLAYLIST_PREFIX, $catName),
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

        $this->session->set('CAT_PLAYLIST_ID', $responseData['id']);
        $this->session->set('CAT_NAME', $catName);
    }

    /**
     * Returns array of random spotify playlists ids based on user choice
     *
     * @param  string $accessToken
     * @param  string $market
     * @param  string $query
     * @return string[]
     */
    public function getPlaylistsIds(string $accessToken, string $market, string $query = CatHat::SHORT_BEANIE->toQuery()): array
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
     * collects all playlists created with this app
     *
     * @return array
     */
    public function getMewsickPlaylists(): array
    {
        $accessToken = $this->session->get('ACCESS_TOKEN');

        $requestData = new SpotifyApiRequestDTO(
            'GET',
            sprintf('%s/me/playlists', Constant::SPOTIFY_API_URL),
            ['Authorization' => sprintf('Bearer %s', $accessToken)]
        );

        $result = $this->processApiRequest($requestData);

        return $this->processUserPlaylists($result);
    }

    /**
     * Process user choice and adds tracks to created playlist
     *
     * @param  string  $data
     * @return void
     */
    public function addTracksToPlaylist(string $query): void
    {
        $accessToken    = $this->session->get('ACCESS_TOKEN');
        $market         = $this->session->get('API_USER_COUNTRY');
        $catPlaylistId  = $this->session->get('CAT_PLAYLIST_ID');
        $sampleLenght   = $this->session->get('SAMPLE_LENGTH');
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
    public function updatePlaylistItems(string $accessToken, string $playlistId, array $tracks): void
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
     * @return void
     */
    public function processCurrentUserData(): void
    {
        $requestData = new SpotifyApiRequestDTO(
            'GET',
            sprintf('%s/me', Constant::SPOTIFY_API_URL),
            ['Authorization' => sprintf('Bearer %s', $this->session->get('ACCESS_TOKEN'))]
        );

        $responseData = $this->processApiRequest($requestData);

        $this->session->set('API_USER_COUNTRY', $responseData['country']);
        $this->session->set('API_USER_NAME', $responseData['display_name']);
        $this->session->set('API_USER_ID', $responseData['id']);
    }

    /**
     * Process api request
     *
     * @param  SpotifyApiRequestDTO $reqeustData
     * @return array
     */
    public function processApiRequest(SpotifyApiRequestDTO $reqeustData): ?array
    {
        try {
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

        } catch (RequestException $e) {
            throw new RequestException(
                $e->getMessage(),
                $e->getRequest(),
                $e->getResponse()
            );
        } catch (ConnectException $e) {
            throw new ConnectException(
                $e->getMessage(),
                $e->getRequest()
            );
        }
    }

    /**
     * Updates playlist cover photo
     *
     * @return void
     */
    public function updatePlaylistCoverPhoto(): void
    {
        $catPhotoName  = $this->session->get('CAT_PHOTO_NAME');
        $accessToken   = $this->session->get('ACCESS_TOKEN');
        $catPlaylistId = $this->session->get('CAT_PLAYLIST_ID');

        $photoPath = sprintf('images/cats/playlist_cover/%s.jpg', $catPhotoName);
        $data      = file_get_contents($photoPath);
        $body      = base64_encode($data);

        $requestData  = new SpotifyApiRequestDTO(
            'PUT',
            sprintf('%s/playlists/%s/images', Constant::SPOTIFY_API_URL, $catPlaylistId),
            [
                'Authorization' => sprintf('Bearer %s', $accessToken),
                'Content-Type'  => 'image/jpeg'
            ],
            $body
        );

        $this->processApiRequest($requestData);

        $this->fileService->removeCoverPhoto($catPhotoName);
    }

    /**
     * Collects playlist url
     *
     * @return string
     */
    public function getPlaylistUrl(): string
    {
        $playlistId  = $this->session->get('CAT_PLAYLIST_ID');
        $accessToken = $this->session->get('ACCESS_TOKEN');
        $market      = $this->session->get('API_USER_COUNTRY');

        $requestData = new SpotifyApiRequestDTO(
            'GET',
            sprintf('%s/playlists/%s?market=%s', Constant::SPOTIFY_API_URL, $playlistId, $market),
            ['Authorization' => sprintf('Bearer %s', $accessToken)]
        );

        $response = $this->processApiRequest($requestData);

        return $response['external_urls']['spotify'];
    }

    /**
     * Get headers for access token request
     *
     * @return array
     */
    private function getAccessTokenRequestHeaders(): array
    {
        $key = base64_encode(sprintf(
            '%s:%s',
            $this->CLIENT_ID,
            $this->CLIENT_SECRET
        ));

        return [
            'Content-Type'  => Constant::CONTENT_TYPE,
            'Authorization' => sprintf('Basic %s', $key)
        ];
    }

    /**
     * Handles api access token request
     *
     * @param  string $body
     * @return void
     */
    private function handleApiTokenRequest(string $body): void
    {
        $headers  = $this->getAccessTokenRequestHeaders();
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
    private function processSessionValues(ResponseInterface $response): void
    {
        $responseData = json_decode(
            $response->getBody()->getContents(),
            true
        );

        $this->session->set('ACCESS_TOKEN', $responseData['access_token']);

        if (isset($responseData['refresh_token'])) {
            $this->session->set('REFRESH_TOKEN', $responseData['refresh_token']);
        }
    }

    /**
     * Filters request response to collect only playlists created with this app
     *
     * @param  array $requestResult
     * @return array
     */
    private function processUserPlaylists(array $requestResult): array
    {
        $playlistNamePrefix = Constant::SPOTIFY_PLAYLIST_PREFIX;

        return array_filter(array_map(
            fn($playlist) => str_starts_with($playlist['name'], $playlistNamePrefix) ? $this->processUserPlaylistData($playlist) : null,
            $requestResult['items']
        ));
    }

    /**
     * Process playlist data and returns only needed data
     *
     * @param  array $playlist
     * @return SpotifyPlaylistDTO
     */
    private function processUserPlaylistData(array $playlist): SpotifyPlaylistDTO
    {
        $offset = strlen(Constant::SPOTIFY_PLAYLIST_PREFIX) + 1;

        return new SpotifyPlaylistDTO(
            substr($playlist['name'], $offset),
            $playlist['name'],
            $playlist['description'],
            $playlist['images'][0]['url'],
            $playlist['uri']
        );
    }
}