<?php

namespace App\Tests\Entity;

use App\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

// Unit-тесты сущности Product
class ProductTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = self::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidProduct(): void
    {
        $product = (new Product())
            ->setName('MacBook Pro')
            ->setDescription('High-end laptop from Apple')
            ->setPrice(2499.99)
            ->setCategories(['Laptops', 'Apple']);

        $errors = $this->validator->validate($product);
        $this->assertCount(0, $errors);
    }

    public function testInvalidName(): void
    {
        $product = (new Product())
            ->setName('')  // Пустое имя
            ->setPrice(100)
            ->setCategories(['Electronics']);

        $errors = $this->validator->validate($product);
        $this->assertGreaterThan(0, count($errors));

        $errorMessages = array_map(fn($error) => $error->getMessage(), iterator_to_array($errors));
        $this->assertContains('Название не может быть пустым', $errorMessages);
    }

    public function testInvalidPrice(): void
    {
        $product = (new Product())
            ->setName('iPhone 14')
            ->setPrice(-150)  // Отрицательная цена
            ->setCategories(['Smartphones']);

        $errors = $this->validator->validate($product);
        $this->assertGreaterThan(0, count($errors));

        $errorMessages = array_map(fn($error) => $error->getMessage(), iterator_to_array($errors));
        $this->assertContains('Цена должна быть положительным числом', $errorMessages);
    }

    public function testInvalidCategory(): void
    {
        $product = (new Product())
            ->setName('Galaxy S23')
            ->setPrice(799.99)
            ->setCategories(['', str_repeat('A', 101)]);  // Пустая категория и слишком длинная

        $errors = $this->validator->validate($product);
        $this->assertGreaterThan(0, count($errors));

        $errorMessages = array_map(fn($error) => $error->getMessage(), iterator_to_array($errors));
        $this->assertContains('Категория не может быть пустой', $errorMessages);
        $this->assertContains('Категория не может быть длиннее 100 символов', $errorMessages);
    }

    public function testDescriptionMaxLength(): void
    {
        $product = (new Product())
            ->setName('Dell XPS 13')
            ->setPrice(1299.99)
            ->setDescription(str_repeat('A', 1001)) // Слишком длинное описание
            ->setCategories(['Ultrabooks']);

        $errors = $this->validator->validate($product);
        $this->assertGreaterThan(0, count($errors));

        $errorMessages = array_map(fn($error) => $error->getMessage(), iterator_to_array($errors));
        $this->assertContains('Описание не может быть длиннее 1000 символов', $errorMessages);
    }
}
