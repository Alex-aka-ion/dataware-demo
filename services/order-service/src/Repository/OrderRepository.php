<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * Репозиторий для работы с сущностью Order.
 *
 * Этот класс предоставляет методы для выполнения запросов к базе данных,
 * связанных с сущностью Order, используя возможности Doctrine ORM.
 *
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    /**
     * Конструктор репозитория OrderRepository.
     *
     * Инициализирует репозиторий с помощью менеджера реестра Doctrine и указывает,
     * что данный репозиторий работает с сущностью Order.
     *
     * @param ManagerRegistry $registry Менеджер реестра для управления сущностями Doctrine.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /**
     * Находит заказы по идентификатору продукта.
     *
     * Этот метод выполняет запрос для поиска всех заказов, которые содержат
     * указанный идентификатор продукта в списке товаров заказа.
     *
     * @param string $productId Идентификатор продукта (UUID в строковом формате).
     * @return Order[] Массив заказов, содержащих указанный продукт.
     */
    public function findByProductId(string $productId): array
    {
        return $this->createQueryBuilder('o')
            ->join('o.orderItems', 'oi')
            ->where('oi.productId = :productId')
            ->setParameter('productId', Uuid::fromString($productId)) // Передаём UUID
            ->getQuery()
            ->getResult();
    }
}
