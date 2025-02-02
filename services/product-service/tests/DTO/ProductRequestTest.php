<?php
namespace App\Tests\DTO;

use App\DTO\ProductRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

// Unit-тесты DTO
class ProductRequestTest extends TestCase
{
    private $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testValidProductRequest(): void
    {
        $productRequest = new ProductRequest(
            name: 'Laptop',
            price: 1499.99,
            categories: ['Electronics'],
            description: 'High-end gaming laptop'
        );

        $errors = $this->validator->validate($productRequest);
        $this->assertCount(0, $errors, 'Ожидается отсутствие ошибок для корректных данных.');
    }

    public function testInvalidProductName(): void
    {
        $productRequest = new ProductRequest(
            name: '', // Пустое имя
            price: 1000,
            categories: ['Electronics'],
            description: 'Invalid product'
        );

        $errors = $this->validator->validate($productRequest);
        $this->assertGreaterThan(0, count($errors), 'Ожидаются ошибки из-за пустого имени.');
    }

    public function testInvalidProductNameType(): void
    {
        $productRequest = new ProductRequest(
            name: ['Not', 'a', 'string'], // Неверный тип данных
            price: 1000,
            categories: ['Electronics'],
            description: 'Invalid product'
        );

        $errors = $this->validator->validate($productRequest);
        $this->assertGreaterThan(0, count($errors), 'Ожидаются ошибки из-за некорректного типа данных для имени.');
    }

    public function testNegativePrice(): void
    {
        $productRequest = new ProductRequest(
            name: 'Laptop',
            price: -100, // Отрицательная цена
            categories: ['Electronics'],
            description: 'Invalid price'
        );

        $errors = $this->validator->validate($productRequest);
        $this->assertGreaterThan(0, count($errors), 'Ожидаются ошибки из-за отрицательной цены.');
    }

    public function testInvalidPriceType(): void
    {
        $productRequest = new ProductRequest(
            name: 'Laptop',
            price: 'invalid_price', // Неверный тип данных для цены
            categories: ['Electronics'],
            description: 'Invalid price type'
        );

        $errors = $this->validator->validate($productRequest);
        $this->assertGreaterThan(0, count($errors), 'Ожидаются ошибки из-за некорректного типа данных для цены.');
    }

    public function testEmptyCategories(): void
    {
        $productRequest = new ProductRequest(
            name: 'Laptop',
            price: 1000,
            categories: [], // Пустой массив категорий
            description: 'No categories provided'
        );

        $errors = $this->validator->validate($productRequest);
        $this->assertGreaterThan(0, count($errors), 'Ожидаются ошибки из-за отсутствия категорий.');
    }

    public function testInvalidCategoriesType(): void
    {
        $productRequest = new ProductRequest(
            name: 'Laptop',
            price: 1000,
            categories: 'Not an array', // Неверный тип данных для категорий
            description: 'Invalid categories type'
        );

        $errors = $this->validator->validate($productRequest);
        $this->assertGreaterThan(0, count($errors), 'Ожидаются ошибки из-за некорректного типа данных для категорий.');
    }

    public function testInvalidCategoryItemType(): void
    {
        $productRequest = new ProductRequest(
            name: 'Laptop',
            price: 1000,
            categories: [123, 456], // Некорректные элементы в массиве категорий
            description: 'Invalid category items'
        );

        $errors = $this->validator->validate($productRequest);
        $this->assertGreaterThan(0, count($errors), 'Ожидаются ошибки из-за некорректных элементов категорий.');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->validator = null;
    }
}

