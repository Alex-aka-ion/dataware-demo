<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Репозиторий для управления сущностью Product.
 *
 * Этот класс предоставляет методы для взаимодействия с таблицей продуктов в базе данных,
 * включая поиск по имени и другие операции, связанные с продуктами.
 */
class ProductRepository extends ServiceEntityRepository
{
    /**
     * Конструктор репозитория продуктов.
     *
     * @param ManagerRegistry $registry Менеджер реестра Doctrine для управления сущностями.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * Поиск продуктов по имени.
     *
     * Метод выполняет поиск продуктов, имя которых содержит указанную подстроку.
     * Поиск регистронезависимый и упорядочивает результаты в алфавитном порядке.
     *
     * @param string $name Имя продукта или часть имени для поиска.
     * @return Product[] Массив найденных продуктов.
     */
    public function findByName(string $name): array
    {
        return $this->createQueryBuilder('p')
            ->where('LOWER(p.name) LIKE LOWER(:name)')
            ->setParameter('name', '%' . $name . '%')
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
