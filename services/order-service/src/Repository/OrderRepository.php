<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

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
