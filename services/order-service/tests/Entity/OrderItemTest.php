<?php

namespace App\Tests\Entity;

use App\Entity\Order;
use App\Entity\OrderItem;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

class OrderItemTest extends TestCase
{
    private $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testValidOrderItem(): void
    {
        $order = new Order();
        $order->setDeliveryAddress('ул. Ленина, д. 1');

        $orderItem = new OrderItem();
        $orderItem->setOrder($order);
        $orderItem->setProductId('550e8400-e29b-41d4-a716-446655440000');
        $orderItem->setQuantity(3);
        $orderItem->setPrice(1999.99);

        $errors = $this->validator->validate($orderItem);
        $this->assertCount(0, $errors, 'Ожидается отсутствие ошибок для корректного элемента заказа.');
    }

    public function testMissingProductId(): void
    {
        $order = new Order();
        $order->setDeliveryAddress('ул. Ленина, д. 1');

        $orderItem = new OrderItem();
        $orderItem->setOrder($order);
        $orderItem->setQuantity(3);
        $orderItem->setPrice(1999.99);

        $errors = $this->validator->validate($orderItem);
        $this->assertGreaterThan(0, count($errors), 'Ожидаются ошибки из-за отсутствия productId.');
    }

    public function testInvalidProductIdFormat(): void
    {
        $order = new Order();
        $order->setDeliveryAddress('ул. Ленина, д. 1');

        $orderItem = new OrderItem();
        $orderItem->setOrder($order);
        $orderItem->setProductId('invalid-uuid');
        $orderItem->setQuantity(3);
        $orderItem->setPrice(1999.99);

        $errors = $this->validator->validate($orderItem);
        $this->assertGreaterThan(0, count($errors), 'Ожидаются ошибки из-за неправильного формата UUID для productId.');
    }

    public function testNegativeQuantity(): void
    {
        $order = new Order();
        $order->setDeliveryAddress('ул. Ленина, д. 1');

        $orderItem = new OrderItem();
        $orderItem->setOrder($order);
        $orderItem->setProductId('550e8400-e29b-41d4-a716-446655440000');
        $orderItem->setQuantity(-5);
        $orderItem->setPrice(1999.99);

        $errors = $this->validator->validate($orderItem);
        $this->assertGreaterThan(0, count($errors), 'Ожидаются ошибки из-за отрицательного количества товара.');
    }

    public function testZeroPrice(): void
    {
        $order = new Order();
        $order->setDeliveryAddress('ул. Ленина, д. 1');

        $orderItem = new OrderItem();
        $orderItem->setOrder($order);
        $orderItem->setProductId('550e8400-e29b-41d4-a716-446655440000');
        $orderItem->setQuantity(2);
        $orderItem->setPrice(0);

        $errors = $this->validator->validate($orderItem, null, ['StrictValidation']);
        $this->assertGreaterThan(0, count($errors), 'Ожидаются ошибки из-за нулевой цены товара.');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->validator = null;
    }
}
