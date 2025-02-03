<?php

namespace App\DataFixtures;

use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ProductFixtures extends Fixture
{
    private string $environment;

    // Внедрение параметров окружения через DI
    public function __construct(ParameterBagInterface $params)
    {
        $this->environment = $params->get('kernel.environment');
    }

    public function load(ObjectManager $manager): void
    {
        // Проверка окружения
        if ($this->environment !== 'dev') {
            return; // Не загружать данные, если не dev
        }

        $faker = Factory::create('ru_RU'); // Faker для генерации данных на русском

        $electronicsCategories = [
            'Смартфоны', 'Ноутбуки', 'Планшеты', 'Аудио', 'Гаджеты',
            'Телевизоры', 'Игровые консоли', 'Мониторы', 'Камеры', 'Умные часы'
        ];

        $productTypes = [
            'Смартфон', 'Ноутбук', 'Планшет', 'Гарнитура', 'Умные часы',
            'Игровая консоль', 'Телевизор', 'Камера', 'Монитор', 'Портативная колонка'
        ];

        for ($i = 0; $i < 10; $i++) {
            $product = new Product();

            $productType = $faker->randomElement($productTypes);
            $brand = $faker->company;
            $model = strtoupper($faker->bothify('??-###')); // Генерация случайной модели, например "XZ-457"

            $name = "{$productType} {$brand} {$model}";
            $description = $faker->sentence(12); // Генерация случайного описания
            $price = $faker->randomFloat(2, 5000, 150000); // Цена от 5 000 до 150 000
            $categories = $faker->randomElements($electronicsCategories, $faker->numberBetween(1, 3));

            $product->setName($name);
            $product->setDescription($description);
            $product->setPrice($price);
            $product->setCategories($categories);

            $manager->persist($product);
        }

        $manager->flush();
    }
}
