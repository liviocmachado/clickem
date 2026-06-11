<?php

namespace Clickem\UrlShortener;

use Clickem\UrlShortener\Exceptions\ApiException;
use Clickem\UrlShortener\Exceptions\ClickemException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;

class Clickem
{
    private const BASE_URL = 'https://clickem.me/api/';

    private Client $http;

    public function __construct(
        private readonly string $token,
        string $baseUrl = self::BASE_URL,
    ) {
        $this->http = new Client([
            'base_uri' => $baseUrl,
            'headers'  => [
                'Authorization' => "Bearer {$this->token}",
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ],
        ]);
    }

    /**
     * @throws ApiException
     * @throws ClickemException
     */
    public function shorten(string $originalUrl, ?string $expiresAt = null): ShortUrl
    {
        $payload = ['original_url' => $originalUrl];

        if ($expiresAt !== null) {
            $payload['expires_at'] = $expiresAt;
        }

        try {
            $response = $this->http->post('short-urls', ['json' => $payload]);

            $body = json_decode((string) $response->getBody(), true);

            return ShortUrl::fromArray($body['data']);
        } catch (ClientException $e) {
            $body = json_decode((string) $e->getResponse()->getBody(), true);
            $message = $body['message'] ?? $e->getMessage();
            $errors  = $body['errors'] ?? [];

            throw new ApiException($message, $e->getCode(), $errors);
        } catch (GuzzleException $e) {
            throw new ClickemException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
