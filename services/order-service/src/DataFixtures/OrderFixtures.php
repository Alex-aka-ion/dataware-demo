<?php

namespace App\DataFixtures;

use App\Entity\Order;
use App\Entity\OrderItem;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OrderFixtures extends Fixture
{
    private HttpClientInterface $httpClient;

    private string $environment;

    // Внедрение параметров окружения через DI
    public function __construct(HttpClientInterface $httpClient, ParameterBagInterface $params)
    {
        $this->environment = $params->get('kernel.environment');
        $this->httpClient = $httpClient;
    }

    public function load(ObjectManager $manager): void
    {
        // Проверка окружения
        if ($this->environment !== 'dev') {
            return; // Не загружать данные, если не dev
        }

        $faker = Factory::create('ru_RU');

        // Получение списка продуктов из product-service
        try {
            $response = $this->httpClient->request('GET', 'http://product-service/api/products');
            $products = json_decode($response->getContent(), true);
        } catch (\Exception $e) {
            throw new \RuntimeException('Ошибка при получении данных из product-service: ' . $e->getMessage());
        }

        if (empty($products)) {
            throw new \RuntimeException('Нет доступных продуктов для создания заказов.');
        }

        // Генерация 5 заказов
        for ($i = 0; $i < 5; $i++) {
            $order = new Order();
            $order->setDeliveryAddress($faker->address);

            $numItems = $faker->numberBetween(1, 4); // Количество позиций в заказе

            for ($j = 0; $j < $numItems; $j++) {
                $randomProduct = $faker->randomElement($products);

                $orderItem = new OrderItem();
                $orderItem->setProductId($randomProduct['id']);
                $orderItem->setQuantity($faker->numberBetween(1, 10));
                $orderItem->setPrice($randomProduct['price']);
                $orderItem->setOrder($order);

                $manager->persist($orderItem);
            }

            $manager->persist($order);
        }

        $manager->flush();
    }
}
