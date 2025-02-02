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
        // Создание валидатора с аннотациями
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()  // Включаем поддержку атрибутов вместо аннотаций
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
        $this->assertCount(0, $errors, 'Ожидается отсутствие ошибок.');
    }

    public function testInvalidProductName(): void
    {
        $productRequest = new ProductRequest(
            name: '',  // Ошибка: пустое имя
            price: 1000,
            categories: ['Electronics'],
            description: 'Invalid product'
        );

        $errors = $this->validator->validate($productRequest);
        $this->assertGreaterThan(0, count($errors), 'Ожидаются ошибки из-за пустого имени.');
    }

    public function testNegativePrice(): void
    {
        $productRequest = new ProductRequest(
            name: 'Laptop',
            price: -100,  // Ошибка: отрицательная цена
            categories: ['Electronics'],
            description: 'Invalid price'
        );

        $errors = $this->validator->validate($productRequest);
        $this->assertGreaterThan(0, count($errors), 'Ожидаются ошибки из-за отрицательной цены.');
    }

    public function testEmptyCategories(): void
    {
        $productRequest = new ProductRequest(
            name: 'Laptop',
            price: 1000,
            categories: [],
            description: 'No categories provided'
        );

        $errors = $this->validator->validate($productRequest);
        $this->assertGreaterThan(0, count($errors), 'Ожидаются ошибки из-за отсутствия категорий.');
    }
}
