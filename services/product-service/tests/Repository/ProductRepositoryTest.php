<?php

namespace App\Tests\Repository;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\ORM\EntityManagerInterface;

// Интеграционные тесты репозитория
class ProductRepositoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager = null;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();

        // Очистка таблицы перед каждым тестом
        $connection = $this->entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();
        $connection->executeStatement($platform->getTruncateTableSQL('product', true));
    }

    public function testFindByNameReturnsMatchingProducts(): void
    {
        // Создание тестовых данных
        $product1 = new Product();
        $product1->setName('Test Product A')
            ->setDescription('Описание продукта A')
            ->setPrice(1499.99)
            ->setCategories(['Электроника', 'Компьютеры']);

        $product2 = new Product();
        $product2->setName('Another Product')
            ->setDescription('Описание другого продукта')
            ->setPrice(999.99)
            ->setCategories(['Домашняя техника']);

        $this->entityManager->persist($product1);
        $this->entityManager->persist($product2);
        $this->entityManager->flush();

        // Тестирование метода
        /** @var ProductRepository $productRepository */
        $productRepository = $this->entityManager->getRepository(Product::class);
        $results = $productRepository->findByName('test');

        $this->assertCount(1, $results);
        $this->assertSame('Test Product A', $results[0]->getName());
        $this->assertSame('Описание продукта A', $results[0]->getDescription());
        $this->assertEquals(1499.99, $results[0]->getPrice());
        $this->assertSame(['Электроника', 'Компьютеры'], $results[0]->getCategories());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null; // Очистка для предотвращения утечек памяти
    }
}
