<?php

namespace App\Repository;

use App\Entity\OrderItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Репозиторий для работы с сущностью OrderItem.
 *
 * Этот класс предоставляет методы для выполнения запросов к базе данных,
 * связанных с сущностью OrderItem, используя возможности Doctrine ORM.
 */
class OrderItemRepository extends ServiceEntityRepository
{
    /**
     * Конструктор репозитория OrderItemRepository.
     *
     * Инициализирует репозиторий с помощью менеджера реестра Doctrine и указывает,
     * что данный репозиторий работает с сущностью OrderItem.
     *
     * @param ManagerRegistry $registry Менеджер реестра для управления сущностями.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderItem::class);
    }
}
