<?php

namespace App\Tests\Entity;

use App\Entity\Order;
use App\Entity\OrderItem;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\ConstraintViolation;

class OrderTest extends TestCase
{
    private $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testValidOrder(): void
    {
        $order = new Order();
        $order->setDeliveryAddress('ул. Пушкина, д. 10, кв. 5');

        $orderItem = new OrderItem();
        $orderItem->setProductId('550e8400-e29b-41d4-a716-446655440000');
        $orderItem->setQuantity(2);

        $order->addOrderItem($orderItem);

        $errors = $this->validator->validate($order);
        $this->assertCount(0, $errors, 'Ожидается отсутствие ошибок для корректного заказа.');
    }

    public function testMissingDeliveryAddress(): void
    {
        $order = new Order();
        $errors = $this->validator->validate($order);

        $this->assertGreaterThan(0, count($errors), 'Ожидаются ошибки из-за отсутствия адреса доставки.');

        /** @var ConstraintViolation $violation */
        $violation = $errors[0];
        $this->assertEquals('Адрес доставки обязателен', $violation->getMessage());
    }

    public function testInvalidDeliveryAddressLength(): void
    {
        $order = new Order();
        $order->setDeliveryAddress('123'); // Слишком короткий адрес

        $errors = $this->validator->validate($order);
        $this->assertGreaterThan(0, count($errors), 'Ожидаются ошибки из-за короткого адреса доставки.');
    }

    public function testAddOrderItem(): void
    {
        $order = new Order();
        $order->setDeliveryAddress('ул. Пушкина, д. 10, кв. 5');

        $orderItem = new OrderItem();
        $orderItem->setProductId('550e8400-e29b-41d4-a716-446655440000');
        $orderItem->setQuantity(3);

        $order->addOrderItem($orderItem);

        $this->assertCount(1, $order->getOrderItems(), 'Ожидается один товар в заказе.');
    }

    public function testOrderCreatedAt(): void
    {
        $order = new Order();
        $this->assertNotNull($order->getCreatedAt(), 'Ожидается наличие даты создания заказа.');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->validator = null;
    }
}
