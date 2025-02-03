<?php

namespace App\Tests\DTO;

use App\DTO\OrderRequest;
use App\DTO\OrderItemRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

// Unit-тесты DTO
class OrderRequestTest extends TestCase
{
    private $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testValidOrderRequest(): void
    {
        $orderItem = new OrderItemRequest(
            productId: '550e8400-e29b-41d4-a716-446655440000',
            quantity: 2
        );

        $orderRequest = new OrderRequest(
            deliveryAddress: '123 улица, город, страна',
            products: [$orderItem]
        );

        $errors = $this->validator->validate($orderRequest);
        $this->assertCount(0, $errors, 'Ожидается отсутствие ошибок для корректных данных.');
    }

    public function testMissingDeliveryAddress(): void
    {
        $orderItem = new OrderItemRequest(
            productId: '550e8400-e29b-41d4-a716-446655440000',
            quantity: 2
        );

        $orderRequest = new OrderRequest(
            deliveryAddress: '', // Пустой адрес доставки
            products: [$orderItem]
        );

        $errors = $this->validator->validate($orderRequest);
        $this->assertGreaterThan(0, count($errors), 'Ожидаются ошибки из-за отсутствия адреса доставки.');
    }

    public function testInvalidDeliveryAddressType(): void
    {
        $orderItem = new OrderItemRequest(
            productId: '550e8400-e29b-41d4-a716-446655440000',
            quantity: 2
        );

        $orderRequest = new OrderRequest(
            deliveryAddress: 12345, // Неверный тип данных для адреса доставки
            products: [$orderItem]
        );

        $errors = $this->validator->validate($orderRequest);
        $this->assertGreaterThan(0, count($errors), 'Ожидаются ошибки из-за некорректного типа данных для адреса доставки.');
    }

    public function testMissingProducts(): void
    {
        $orderRequest = new OrderRequest(
            deliveryAddress: '123 улица, город, страна',
            products: [] // Пустой список товаров
        );

        $errors = $this->validator->validate($orderRequest);
        $this->assertGreaterThan(0, count($errors), 'Ожидаются ошибки из-за отсутствия товаров в заказе.');
    }

    public function testInvalidProductItem(): void
    {
        $invalidOrderItem = new OrderItemRequest(
            productId: 'invalid-uuid', // Неверный UUID
            quantity: -1               // Отрицательное количество
        );

        $orderRequest = new OrderRequest(
            deliveryAddress: '123 улица, город, страна',
            products: [$invalidOrderItem]
        );

        $errors = $this->validator->validate($orderRequest);
        $this->assertGreaterThan(0, count($errors), 'Ожидаются ошибки из-за некорректных данных в товаре.');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->validator = null;
    }
}
