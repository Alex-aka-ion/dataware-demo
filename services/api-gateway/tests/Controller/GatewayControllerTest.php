<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpClient\Exception\ServerException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GatewayControllerTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot(); // Сохраняем контейнер для повторных запросов
    }

    private function mockHttpClient(array $responses): void
    {
        $mockHttpClient = new MockHttpClient($responses);
        self::getContainer()->set(HttpClientInterface::class, $mockHttpClient);
    }

    public function testGetProduct(): void
    {
        $this->mockHttpClient([
            new MockResponse(json_encode(['id' => '1', 'name' => 'Test Product']), ['http_code' => 200])
        ]);

        $this->client->request('GET', '/api/products/1');
        $this->assertResponseIsSuccessful();
    }

    public function testCreateProduct(): void
    {
        $this->mockHttpClient([
            new MockResponse(json_encode(['id' => '1', 'name' => 'New Product']), ['http_code' => 201])
        ]);

        $this->client->request('POST', '/api/products', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['name' => 'New Product']));
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    public function testUpdateProduct(): void
    {
        $this->mockHttpClient([
            new MockResponse(json_encode(['id' => '1', 'name' => 'Updated Product']), ['http_code' => 200])
        ]);

        $this->client->request('PUT', '/api/products/1', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['name' => 'Updated Product']));
        $this->assertResponseIsSuccessful();
    }

    public function testDeleteProduct(): void
    {
        $this->mockHttpClient([
            new MockResponse('', ['http_code' => 204])
        ]);

        $this->client->request('DELETE', '/api/products/1');
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testSearchProducts(): void
    {
        $this->mockHttpClient([
            new MockResponse(json_encode([['id' => '1', 'name' => 'Product 1'], ['id' => '2', 'name' => 'Product 2']]), ['http_code' => 200])
        ]);

        $this->client->request('GET', '/api/products/search?name=Product');
        $this->assertResponseIsSuccessful();
    }

    public function testGetOrder(): void
    {
        $this->mockHttpClient([
            new MockResponse(json_encode(['id' => '1', 'deliveryAddress' => '123 Street']), ['http_code' => 200])
        ]);

        $this->client->request('GET', '/api/orders/1');
        $this->assertResponseIsSuccessful();
    }

    public function testCreateOrder(): void
    {
        $this->mockHttpClient([
            new MockResponse(json_encode(['id' => '1', 'deliveryAddress' => '123 Street']), ['http_code' => 201])
        ]);

        $this->client->request('POST', '/api/orders', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['deliveryAddress' => '123 Street']));
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    public function testUpdateOrder(): void
    {
        $this->mockHttpClient([
            new MockResponse(json_encode(['id' => '1', 'deliveryAddress' => 'Updated Address']), ['http_code' => 200])
        ]);

        $this->client->request('PUT', '/api/orders/1', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['deliveryAddress' => 'Updated Address']));
        $this->assertResponseIsSuccessful();
    }

    public function testDeleteOrder(): void
    {
        $this->mockHttpClient([
            new MockResponse('', ['http_code' => 204])
        ]);

        $this->client->request('DELETE', '/api/orders/1');
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testSearchOrders(): void
    {
        $this->mockHttpClient([
            new MockResponse(json_encode([['id' => '1'], ['id' => '2']]), ['http_code' => 200])
        ]);

        $this->client->request('GET', '/api/orders/search?productId=123');
        $this->assertResponseIsSuccessful();
    }

    public function testForbiddenAccess(): void
    {
        $this->client->request('PATCH', '/api/products/1');
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $this->client->request('PATCH', '/api/orders/1');
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testServiceUnavailable(): void
    {
        $mockResponse = new MockResponse('', [
            'http_code' => 500
        ]);

        $mockHttpClient = new MockHttpClient(function () use ($mockResponse) {
            throw new ServerException($mockResponse);
        });

        self::getContainer()->set(HttpClientInterface::class, $mockHttpClient);

        $this->client->request('GET', '/api/products/uuid');

        $this->assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
