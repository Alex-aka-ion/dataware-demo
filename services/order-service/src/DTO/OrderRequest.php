<?php
namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class OrderRequest
{
    #[Assert\NotBlank(message: "Адрес доставки обязателен.")]
    public string $deliveryAddress;

    /**
     * @var OrderItemRequest[]
     */
    #[Assert\NotBlank(message: "Необходимо указать товары в заказе.")]
    #[Assert\Count(min: 1, minMessage: "Необходимо указать как минимум один товар.")]
    #[Assert\Valid] // Проверка каждого OrderItem
    public array $products;

    public function __construct(string $deliveryAddress, array $products)
    {
        $this->deliveryAddress = $deliveryAddress;
        $this->products = $products;
    }
}