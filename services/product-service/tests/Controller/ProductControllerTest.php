<?php

namespace App\Tests\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

// Функциональные тесты API
class ProductControllerTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testCreateProduct(): void
    {
        $productData = [
            'name' => 'Product One',
            'price' => 1499.99,
            'categories' => ['CategoryA'],
            'description' => 'Description for Product One'
        ];

        $this->client->request('POST', '/api/products', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($productData));
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $response);
    }

    public function testCreateInvalidProduct(): void
    {
        $invalidProductData = [
            'name' => '', // Пустое имя продукта (ошибка валидации)
            'price' => -100, // Отрицательная цена (ошибка валидации)
            'categories' => 'Not an array', // Неверный тип данных для категорий
            'description' => str_repeat('A', 2000) // Слишком длинное описание
        ];

        $this->client->request('POST', '/api/products', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($invalidProductData));
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $response);
    }

    public function testReadProduct(): void
    {
        // Создание продукта для чтения
        $productData = [
            'name' => 'Product Read Test',
            'price' => 999.99,
            'categories' => ['CategoryB'],
            'description' => 'Description for Product Read Test'
        ];

        $this->client->request('POST', '/api/products', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($productData));
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $productId = $response['id'];

        // Чтение продукта
        $this->client->request('GET', "/api/products/{$productId}");
        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');
    }

    public function testUpdateProduct(): void
    {
        // Создание продукта для обновления
        $productData = [
            'name' => 'Product Update Test',
            'price' => 1299.99,
            'categories' => ['CategoryC'],
            'description' => 'Description for Product Update Test'
        ];

        $this->client->request('POST', '/api/products', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($productData));
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $productId = $response['id'];

        // Обновление продукта
        $updateData = [
            'name' => 'Updated Product',
            'price' => 1399.99,
            'categories' => ['CategoryC', 'UpdatedCategory'],
            'description' => 'Updated description for Product'
        ];

        $this->client->request('PUT', "/api/products/{$productId}", [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($updateData));
        $this->assertResponseIsSuccessful();

        $updatedResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('Updated Product', $updatedResponse['name']);
        $this->assertEquals(1399.99, $updatedResponse['price']);
    }

    public function testInvalidUpdateProduct(): void
    {
        $productData = [
            'name' => 'Valid Product',
            'price' => 1299.99,
            'categories' => ['CategoryE'],
            'description' => 'Valid product description'
        ];

        $this->client->request('POST', '/api/products', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($productData));
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $productId = $response['id'];

        $invalidUpdateData = [
            'name' => '', // Пустое имя продукта (ошибка валидации)
            'price' => -500, // Отрицательная цена (ошибка валидации)
            'categories' => 'Invalid Category', // Неверный тип данных для категорий
            'description' => str_repeat('B', 1500) // Слишком длинное описание
        ];

        $this->client->request('PUT', "/api/products/{$productId}", [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($invalidUpdateData));
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $response);
    }

    public function testDeleteProduct(): void
    {
        // Создание продукта для удаления
        $productData = [
            'name' => 'Product Delete Test',
            'price' => 1099.99,
            'categories' => ['CategoryD'],
            'description' => 'Description for Product Delete Test'
        ];

        $this->client->request('POST', '/api/products', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($productData));
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $productId = $response['id'];

        // Удаление продукта
        $this->client->request('DELETE', "/api/products/{$productId}");
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        // Проверка удаления
        $this->client->request('GET', "/api/products/{$productId}");
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testSearchByName(): void
    {
        // Создание двух продуктов для поиска
        $productData1 = [
            'name' => 'Searchable Product A',
            'price' => 1499.99,
            'categories' => ['CategoryX'],
            'description' => 'Description for Product A'
        ];

        $productData2 = [
            'name' => 'Another Searchable Product B',
            'price' => 1999.99,
            'categories' => ['CategoryY'],
            'description' => 'Description for Product B'
        ];

        $this->client->request('POST', '/api/products', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($productData1));
        $this->client->request('POST', '/api/products', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($productData2));

        // Поиск продуктов по имени
        $this->client->request('GET', '/api/products/search?name=Searchable');
        $this->assertResponseIsSuccessful();

        $searchResults = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(2, $searchResults);

        $productNames = array_column($searchResults, 'name');
        $this->assertContains('Searchable Product A', $productNames);
        $this->assertContains('Another Searchable Product B', $productNames);
    }

    public function testShowProductNotFound(): void
    {
        $this->client->request('GET', '/api/products/invalid-uuid');
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $validUuid = Uuid::v4()->toRfc4122();
        $this->client->request('GET', "/api/products/{$validUuid}");
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testUpdateProductNotFound(): void
    {
        $data = [
            'name' => 'Updated Product'
        ];

        $this->client->request('PUT', '/api/products/invalid-uuid', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $validUuid = Uuid::v4()->toRfc4122();

        $this->client->request('PUT', "/api/products/{$validUuid}", [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDeleteProductNotFound(): void
    {
        $this->client->request('DELETE', '/api/products/invalid-uuid');
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $validUuid = Uuid::v4()->toRfc4122();
        $this->client->request('DELETE', "/api/products/{$validUuid}");
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->client = null;
    }
}
