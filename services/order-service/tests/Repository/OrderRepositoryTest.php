<?php

namespace App\Tests\Repository;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\ORM\EntityManagerInterface;

// Интеграционные тесты Repository
class OrderRepositoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager = null;
    private OrderRepository $orderRepository;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = self::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->orderRepository = $this->entityManager->getRepository(Order::class);
    }

    public function testFindByProductId(): void
    {
        $productId = '550e8400-e29b-41d4-a716-446655440000';

        // Первый заказ
        $order1 = new Order();
        $order1->setDeliveryAddress('ул. Ленина, д. 1');

        $orderItem1 = new OrderItem();
        $orderItem1->setProductId($productId);
        $orderItem1->setQuantity(2);
        $orderItem1->setPrice(1999.99);

        $orderItem2 = new OrderItem();
        $orderItem2->setProductId('550e8400-e29b-41d4-a716-446655440001');
        $orderItem2->setQuantity(1);
        $orderItem2->setPrice(999.99);

        $order1->addOrderItem($orderItem1);
        $order1->addOrderItem($orderItem2);

        // Второй заказ
        $order2 = new Order();
        $order2->setDeliveryAddress('ул. Гагарина, д. 5');

        $orderItem3 = new OrderItem();
        $orderItem3->setProductId($productId);
        $orderItem3->setQuantity(3);
        $orderItem3->setPrice(1499.99);

        $orderItem4 = new OrderItem();
        $orderItem4->setProductId('550e8400-e29b-41d4-a716-446655440002');
        $orderItem4->setQuantity(4);
        $orderItem4->setPrice(499.99);

        $order2->addOrderItem($orderItem3);
        $order2->addOrderItem($orderItem4);

        // Сохраняем заказы
        $this->entityManager->persist($order1);
        $this->entityManager->persist($order2);
        $this->entityManager->flush();

        // Поиск заказов по productId
        $orders = $this->orderRepository->findByProductId($productId);

        // Проверки
        $this->assertCount(2, $orders, 'Ожидается два заказа с указанным productId.');
        $this->assertEquals('ул. Ленина, д. 1', $orders[0]->getDeliveryAddress(), 'Проверка адреса первого заказа.');
        $this->assertEquals('ул. Гагарина, д. 5', $orders[1]->getDeliveryAddress(), 'Проверка адреса второго заказа.');
    }

    public function testFindByProductIdReturnsEmptyForNonexistentProduct(): void
    {
        $orders = $this->orderRepository->findByProductId('11111111-1111-1111-1111-111111111111');

        $this->assertCount(0, $orders, 'Ожидается отсутствие заказов для несуществующего productId.');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->entityManager->close();
        $this->entityManager = null;
    }
}
