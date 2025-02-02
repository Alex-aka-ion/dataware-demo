<?php

namespace App\Tests\DTO;

use App\DTO\OrderItemRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

class OrderItemRequestTest extends TestCase
{
    private $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testValidOrderItemRequest(): void
    {
        $orderItemRequest = new OrderItemRequest(
            productId: '550e8400-e29b-41d4-a716-446655440000',
            quantity: 5
        );

        $errors = $this->validator->validate($orderItemRequest);
        $this->assertCount(0, $errors, 'Ожидается отсутствие ошибок для корректных данных.');
    }

    public function testInvalidProductId(): void
    {
        $orderItemRequest = new OrderItemRequest(
            productId: 'invalid-uuid', // Некорректный UUID
            quantity: 5
        );

        $errors = $this->validator->validate($orderItemRequest);
        $this->assertGreaterThan(0, count($errors), 'Ожидаются ошибки из-за неверного формата UUID.');
    }

    public function testNullProductId(): void
    {
        $orderItemRequest = new OrderItemRequest(
            productId: null, // Пустой идентификатор
            quantity: 5
        );

        $errors = $this->validator->validate($orderItemRequest);
        $this->assertGreaterThan(0, count($errors), 'Ожидаются ошибки из-за отсутствия идентификатора товара.');
    }

    public function testInvalidQuantity(): void
    {
        $orderItemRequest = new OrderItemRequest(
            productId: '550e8400-e29b-41d4-a716-446655440000',
            quantity: -3 // Отрицательное количество
        );

        $errors = $this->validator->validate($orderItemRequest);
        $this->assertGreaterThan(0, count($errors), 'Ожидаются ошибки из-за отрицательного количества.');
    }

    public function testZeroQuantity(): void
    {
        $orderItemRequest = new OrderItemRequest(
            productId: '550e8400-e29b-41d4-a716-446655440000',
            quantity: 0 // Нулевое количество
        );

        $errors = $this->validator->validate($orderItemRequest);
        $this->assertGreaterThan(0, count($errors), 'Ожидаются ошибки из-за нулевого количества.');
    }

    public function testInvalidDataTypeForQuantity(): void
    {
        $orderItemRequest = new OrderItemRequest(
            productId: '550e8400-e29b-41d4-a716-446655440000',
            quantity: 'invalid' // Неверный тип данных для количества
        );

        $errors = $this->validator->validate($orderItemRequest);
        $this->assertGreaterThan(0, count($errors), 'Ожидаются ошибки из-за некорректного типа данных для количества.');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->validator = null;
    }
}
