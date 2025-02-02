<?php

namespace App\Tests\Controller;

use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class OrderControllerTest extends WebTestCase
{
    private $client;
    private $httpClientMock;
    private $mockResponse;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        // Подключаем мок в контейнер
        /** @var ContainerInterface $container */
        $container = self::getContainer();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);

        // Очистка базы данных перед каждым тестом
        $purger = new ORMPurger($entityManager);
        $purger->purge();
    }

    public function testCreateOrder(): void
    {
        $this->client->disableReboot();
        // Определение последовательности ответов от product-service
        $responses = [
            new MockResponse(json_encode(['price' => 2999.99]), ['http_code' => 200]),
        ];

        // Создание MockHttpClient с последовательными ответами
        $mockHttpClient = new MockHttpClient($responses);

        // Установка MockHttpClient в контейнер Symfony
        static::getContainer()->set(HttpClientInterface::class, $mockHttpClient);

        $orderData = [
            'deliveryAddress' => 'ул. Пушкина, д. 10',
            'products' => [
                [
                    'productId' => '550e8400-e29b-41d4-a716-446655440000',
                    'quantity' => 2
                ]
            ]
        ];

        $this->client->request('POST', '/api/orders', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($orderData));
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    public function testGetOrders(): void
    {
        $this->client->disableReboot();
        // Определение последовательности ответов от product-service
        $responses = [
            new MockResponse(json_encode(['price' => 2999.99]), ['http_code' => 200]),
            new MockResponse(json_encode(['price' => 1999.99]), ['http_code' => 200]),
        ];

        // Создание MockHttpClient с последовательными ответами
        $mockHttpClient = new MockHttpClient($responses);

        // Установка MockHttpClient в контейнер Symfony
        static::getContainer()->set(HttpClientInterface::class, $mockHttpClient);

        // Данные для первого заказа
        $orderData1 = [
            'deliveryAddress' => 'ул. Чехова, д. 7',
            'products' => [
                [
                    'productId' => '550e8400-e29b-41d4-a716-446655440005',
                    'quantity' => 10
                ]
            ]
        ];

        // Создание первого заказа
        $this->client->request('POST', '/api/orders', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($orderData1));
        $response1 = json_decode($this->client->getResponse()->getContent(), true);
        $statusCode1 = $this->client->getResponse()->getStatusCode();

        echo "Response 1: " . json_encode($response1, JSON_PRETTY_PRINT) . "\n";
        echo "Status Code 1: {$statusCode1}\n";

       // $this->assertResponseStatusCodeSame(Response::HTTP_CREATED, 'Первый заказ должен быть создан.');

        // Данные для второго заказа
        $orderData2 = [
            'deliveryAddress' => 'ул. Толстого, д. 12',
            'products' => [
                [
                    'productId' => '550e8400-e29b-41d4-a716-446655440016',
                    'quantity' => 30
                ]
            ]
        ];

        // Создание второго заказа
        $this->client->request('POST', '/api/orders', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($orderData2));
        $response2 = json_decode($this->client->getResponse()->getContent(), true);
        $statusCode2 = $this->client->getResponse()->getStatusCode();

        echo "Response 2: " . json_encode($response2, JSON_PRETTY_PRINT) . "\n";
        echo "Status Code 2: {$statusCode2}\n";

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED, 'Второй заказ должен быть создан.');

        // Проверка получения заказов
        $this->client->request('GET', '/api/orders');
        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $orders = json_decode($this->client->getResponse()->getContent(), true);
        echo "Orders Response: " . json_encode($orders, JSON_PRETTY_PRINT) . "\n";

        $this->assertGreaterThanOrEqual(2, count($orders), 'Ожидается как минимум два заказа в списке.');
    }

    public function testSearchByProductId(): void
    {
        $this->client->disableReboot();

        // Определение последовательности ответов от product-service
        $responses = [
            new MockResponse(json_encode(['price' => 2999.99]), ['http_code' => 200]),
            new MockResponse(json_encode(['price' => 1999.99]), ['http_code' => 200]),
            new MockResponse(json_encode(['price' => 3999.99]), ['http_code' => 200]),
            new MockResponse(json_encode(['price' => 4999.99]), ['http_code' => 200]),
        ];

        // Создание MockHttpClient с последовательными ответами
        $mockHttpClient = new MockHttpClient($responses);

        // Установка MockHttpClient в контейнер Symfony
        static::getContainer()->set(HttpClientInterface::class, $mockHttpClient);

        $productId = '550e8400-e29b-41d4-a716-446655440011';

        $orderData1 = [
            'deliveryAddress' => 'ул. Ленина, д. 5',
            'products' => [
                [
                    'productId' => $productId,
                    'quantity' => 11
                ],
                [
                    'productId' => '550e8400-e29b-41d4-a716-446655440012',
                    'quantity' => 12
                ]
            ]
        ];

        $orderData2 = [
            'deliveryAddress' => 'ул. Гагарина, д. 8',
            'products' => [
                [
                    'productId' => $productId,
                    'quantity' => 11
                ],
                [
                    'productId' => '550e8400-e29b-41d4-a716-446655440013',
                    'quantity' => 13
                ]
            ]
        ];

        $this->client->request('POST', '/api/orders', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($orderData1));
        $this->client->request('POST', '/api/orders', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($orderData2));

        $this->client->request('GET', "/api/orders/search?productId={$productId}");
        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $orders = json_decode($this->client->getResponse()->getContent(), true);
        //var_dump($orders);
        $this->assertCount(2, $orders, 'Ожидается два заказа с указанным productId.');
    }

    public function testUpdateOrder(): void
    {
        $this->client->disableReboot();
        // Сначала создаем заказ
        // Определение последовательности ответов от product-service
        $responses = [
            new MockResponse(json_encode(['price' => 2999.99]), ['http_code' => 200]),
        ];

        // Создание MockHttpClient с последовательными ответами
        $mockHttpClient = new MockHttpClient($responses);

        // Установка MockHttpClient в контейнер Symfony
        static::getContainer()->set(HttpClientInterface::class, $mockHttpClient);

        $orderData = [
            'deliveryAddress' => 'ул. Пушкина, д. 10',
            'products' => [
                [
                    'productId' => '550e8400-e29b-41d4-a716-446655440021',
                    'quantity' => 21
                ]
            ]
        ];

        $this->client->request('POST', '/api/orders', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($orderData));
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $orderId = $response['orderId'];

        // Обновляем заказ
        $updateData = ['deliveryAddress' => 'ул. Лермонтова, д. 15'];
        $this->client->request('PUT', "/api/orders/{$orderId}", [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($updateData));

        $this->assertResponseIsSuccessful();
    }

    public function testDeleteOrder(): void
    {
        $this->client->disableReboot();
        // Создание заказа перед удалением
        // Определение последовательности ответов от product-service
        $responses = [
            new MockResponse(json_encode(['price' => 2999.99]), ['http_code' => 200]),
        ];

        // Создание MockHttpClient с последовательными ответами
        $mockHttpClient = new MockHttpClient($responses);

        // Установка MockHttpClient в контейнер Symfony
        static::getContainer()->set(HttpClientInterface::class, $mockHttpClient);

        $orderData = [
            'deliveryAddress' => 'ул. Чехова, д. 7',
            'products' => [
                [
                    'productId' => '550e8400-e29b-41d4-a716-446655440031',
                    'quantity' => 31
                ]
            ]
        ];

        $this->client->request('POST', '/api/orders', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($orderData));
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $orderId = $response['orderId'];

        // Удаление заказа
        $this->client->request('DELETE', "/api/orders/{$orderId}");
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        // Вторая попытка удаления удаленного заказа
        $this->client->request('DELETE', "/api/orders/{$orderId}");
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->client = null;
        $this->httpClientMock = null;
    }
}
