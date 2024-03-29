<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Const\Constant;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Psr\Http\Message\ResponseInterface;
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
        $key     = base64_encode(sprintf(
            '%s:%s',
            $_ENV['SPOTIFY_API_CLIENT_ID'],
            $_ENV['SPOTIFY_API_CLIENT_SECTRET']
        ));
        $headers = [
            'Content-Type'  => Constant::CONTENT_TYPE,
            'Authorization' => sprintf('Basic %s', $key)
        ];
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
        $headers = [
            'Content-Type' => Constant::CONTENT_TYPE
        ];
        $body    = sprintf(
            'refresh_token=%s&client_id=%s&grant_type=refresh_token',
            $session->get('refresh_token'),
            $_ENV['SPOTIFY_API_CLIENT_ID']
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
            $uri     = sprintf(
                'https://api.spotify.com/v1/artists/%s',
                Constant::ARTIST_ID_FOR_TOKEN_VALIDATION
            );
            $headers = [
                'Authorization' => sprintf('Bearer %s', $access_token)
            ];

            $response = $this->client->request('GET', $uri, ['headers' => $headers]);

            if ($response->getStatusCode() != 200) {
                return false;
            }

            return true;

        } catch (ClientException) {
            $this->refreshAccessToken();
        }
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
        $response = $this->client->request('POST', Constant::ACCESS_TOKEN_URL, [
            'headers' => $headers,
            'body'    => $body
        ]);

        $this->processSessionValues($response);
    }

    /**
     * Adds values to session
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

        $session->set('access_token', $responseData['access_token']);
        $session->set('refresh_token', $responseData['refresh_token']);
    }
}