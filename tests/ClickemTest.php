<?php

namespace Clickem\UrlShortener\Tests;

use Clickem\UrlShortener\Clickem;
use Clickem\UrlShortener\Exceptions\ApiException;
use Clickem\UrlShortener\Exceptions\ClickemException;
use Clickem\UrlShortener\ShortUrl;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ClickemTest extends TestCase
{
    private function makeClient(array $responses, array &$history = []): Clickem
    {
        $mock    = new MockHandler($responses);
        $stack   = HandlerStack::create($mock);
        $stack->push(Middleware::history($history));
        $http = new Client([
            'handler' => $stack,
            'headers' => [
                'Authorization' => 'Bearer test-token',
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ],
        ]);

        $clickem = new Clickem('test-token');

        $ref = new ReflectionClass($clickem);
        $prop = $ref->getProperty('http');
        $prop->setValue($clickem, $http);

        return $clickem;
    }

    private function successResponse(array $overrides = []): Response
    {
        $data = array_merge([
            'id'           => 1,
            'code'         => 'aB3xY1',
            'short_url'    => 'https://clickem.me/aB3xY1',
            'original_url' => 'https://exemplo.com/pagina-muito-longa',
            'expires_at'   => null,
            'created_at'   => '2025-05-21T12:00:00+00:00',
        ], $overrides);

        return new Response(201, ['Content-Type' => 'application/json'], json_encode(['data' => $data]));
    }

    // -------------------------------------------------------------------------
    // shorten() — caminho feliz
    // -------------------------------------------------------------------------

    public function test_shorten_returns_short_url_object(): void
    {
        $clickem = $this->makeClient([$this->successResponse()]);

        $result = $clickem->shorten('https://exemplo.com/pagina-muito-longa');

        $this->assertInstanceOf(ShortUrl::class, $result);
    }

    public function test_shorten_maps_all_fields_correctly(): void
    {
        $clickem = $this->makeClient([$this->successResponse([
            'expires_at' => '2025-12-31',
        ])]);

        $result = $clickem->shorten('https://exemplo.com/pagina-muito-longa', '2025-12-31');

        $this->assertSame(1, $result->id);
        $this->assertSame('aB3xY1', $result->code);
        $this->assertSame('https://clickem.me/aB3xY1', $result->short_url);
        $this->assertSame('https://exemplo.com/pagina-muito-longa', $result->original_url);
        $this->assertSame('2025-12-31', $result->expires_at);
        $this->assertSame('2025-05-21T12:00:00+00:00', $result->created_at);
    }

    public function test_shorten_without_expiry_sends_no_expires_at_field(): void
    {
        $history = [];
        $clickem = $this->makeClient([$this->successResponse()], $history);

        $clickem->shorten('https://exemplo.com/pagina-muito-longa');

        $body = json_decode((string) $history[0]['request']->getBody(), true);
        $this->assertArrayNotHasKey('expires_at', $body);
    }

    public function test_shorten_with_expiry_sends_expires_at_field(): void
    {
        $history = [];
        $clickem = $this->makeClient([$this->successResponse(['expires_at' => '2025-12-31'])], $history);

        $clickem->shorten('https://exemplo.com', '2025-12-31');

        $body = json_decode((string) $history[0]['request']->getBody(), true);
        $this->assertSame('2025-12-31', $body['expires_at']);
    }

    public function test_shorten_sends_correct_original_url(): void
    {
        $history = [];
        $clickem = $this->makeClient([$this->successResponse()], $history);

        $clickem->shorten('https://exemplo.com/pagina-muito-longa');

        $body = json_decode((string) $history[0]['request']->getBody(), true);
        $this->assertSame('https://exemplo.com/pagina-muito-longa', $body['original_url']);
    }

    public function test_shorten_sends_bearer_token_header(): void
    {
        $history = [];
        $clickem = $this->makeClient([$this->successResponse()], $history);

        $clickem->shorten('https://exemplo.com');

        $this->assertSame('Bearer test-token', $history[0]['request']->getHeaderLine('Authorization'));
    }

    public function test_shorten_sends_to_correct_endpoint(): void
    {
        $history = [];
        $clickem = $this->makeClient([$this->successResponse()], $history);

        $clickem->shorten('https://exemplo.com');

        $this->assertSame('POST', $history[0]['request']->getMethod());
        $this->assertStringContainsString('short-urls', (string) $history[0]['request']->getUri());
    }

    // -------------------------------------------------------------------------
    // ShortUrl — comportamentos do DTO
    // -------------------------------------------------------------------------

    public function test_short_url_to_string_returns_short_url(): void
    {
        $clickem = $this->makeClient([$this->successResponse()]);
        $result  = $clickem->shorten('https://exemplo.com');

        $this->assertSame('https://clickem.me/aB3xY1', (string) $result);
    }

    public function test_short_url_to_array_contains_all_keys(): void
    {
        $clickem = $this->makeClient([$this->successResponse()]);
        $result  = $clickem->shorten('https://exemplo.com');
        $array   = $result->toArray();

        foreach (['id', 'code', 'short_url', 'original_url', 'expires_at', 'created_at'] as $key) {
            $this->assertArrayHasKey($key, $array);
        }
    }

    public function test_short_url_expires_at_is_nullable(): void
    {
        $clickem = $this->makeClient([$this->successResponse(['expires_at' => null])]);
        $result  = $clickem->shorten('https://exemplo.com');

        $this->assertNull($result->expires_at);
    }

    // -------------------------------------------------------------------------
    // Erros de API (4xx)
    // -------------------------------------------------------------------------

    public function test_throws_api_exception_on_401(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionCode(401);

        $response = new Response(401, [], json_encode(['message' => 'Unauthenticated.']));
        $clickem  = $this->makeClient([$response]);

        $clickem->shorten('https://exemplo.com');
    }

    public function test_throws_api_exception_on_422_with_validation_errors(): void
    {
        $body = [
            'message' => 'The original url field is required.',
            'errors'  => ['original_url' => ['The original url field is required.']],
        ];
        $response = new Response(422, [], json_encode($body));
        $clickem  = $this->makeClient([$response]);

        try {
            $clickem->shorten('');
            $this->fail('ApiException não foi lançada');
        } catch (ApiException $e) {
            $this->assertSame(422, $e->getStatusCode());
            $this->assertArrayHasKey('original_url', $e->getErrors());
            $this->assertSame('The original url field is required.', $e->getMessage());
        }
    }

    public function test_throws_api_exception_on_403(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionCode(403);

        $response = new Response(403, [], json_encode(['message' => 'Forbidden.']));
        $clickem  = $this->makeClient([$response]);

        $clickem->shorten('https://exemplo.com');
    }

    public function test_api_exception_message_comes_from_api_body(): void
    {
        $response = new Response(401, [], json_encode(['message' => 'Token inválido.']));
        $clickem  = $this->makeClient([$response]);

        try {
            $clickem->shorten('https://exemplo.com');
        } catch (ApiException $e) {
            $this->assertSame('Token inválido.', $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // Erros de rede / transporte
    // -------------------------------------------------------------------------

    public function test_throws_clickem_exception_on_connection_error(): void
    {
        $this->expectException(ClickemException::class);

        $error   = new ConnectException('Connection refused', new Request('POST', 'short-urls'));
        $clickem = $this->makeClient([$error]);

        $clickem->shorten('https://exemplo.com');
    }
}
