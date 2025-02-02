<?php

namespace App\Tests\DTO;

use App\DTO\UpdateOrderRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

class UpdateOrderRequestTest extends TestCase
{
    private $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testValidUpdateOrderRequest(): void
    {
        $request = new UpdateOrderRequest(
            deliveryAddress: 'ул. Пушкина, д. 10, кв. 5'
        );

        $errors = $this->validator->validate($request);
        $this->assertCount(0, $errors, 'Ожидается отсутствие ошибок для корректных данных.');
    }

    public function testMissingDeliveryAddressInUpdate(): void
    {
        $request = new UpdateOrderRequest(
            deliveryAddress: '' // Пустой адрес доставки
        );

        $errors = $this->validator->validate($request);
        $this->assertGreaterThan(0, count($errors), 'Ожидаются ошибки из-за отсутствия адреса доставки.');
    }

    public function testInvalidDeliveryAddressTypeInUpdate(): void
    {
        $request = new UpdateOrderRequest(
            deliveryAddress: 12345 // Неверный тип данных для адреса доставки
        );

        $errors = $this->validator->validate($request);
        $this->assertGreaterThan(0, count($errors), 'Ожидаются ошибки из-за некорректного типа данных для адреса доставки.');
    }

    public function testTooShortDeliveryAddressInUpdate(): void
    {
        $request = new UpdateOrderRequest(
            deliveryAddress: '123' // Адрес слишком короткий (менее 5 символов)
        );

        $errors = $this->validator->validate($request);
        $this->assertGreaterThan(0, count($errors), 'Ожидаются ошибки из-за слишком короткого адреса доставки.');
    }

    public function testTooLongDeliveryAddressInUpdate(): void
    {
        $request = new UpdateOrderRequest(
            deliveryAddress: str_repeat('A', 256) // Адрес превышает 255 символов
        );

        $errors = $this->validator->validate($request);
        $this->assertGreaterThan(0, count($errors), 'Ожидаются ошибки из-за слишком длинного адреса доставки.');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->validator = null;
    }
}
