<?php
namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema(
    title: "OrderRequest",
    description: "Данные для создания нового заказа",
    required: ["deliveryAddress", "products"]
)]
class OrderRequest
{
    #[Assert\NotBlank(message: "Адрес доставки обязателен.")]
    #[OA\Property(
        property: "deliveryAddress",
        description: "Адрес доставки",
        type: "string",
        example: "123 улица, город, страна"
    )]
    public string $deliveryAddress;

    /**
     * @var OrderItemRequest[]
     */
    #[Assert\NotBlank(message: "Необходимо указать товары в заказе.")]
    #[Assert\Count(min: 1, minMessage: "Необходимо указать как минимум один товар.")]
    #[Assert\Valid] // Проверка каждого OrderItem
    #[OA\Property(
        property: "products",
        description: "Список товаров в заказе",
        type: "array",
        items: new OA\Items(ref: "#/components/schemas/OrderItemRequest")
    )]
    public array $products;

    public function __construct(string $deliveryAddress, array $products)
    {
        $this->deliveryAddress = $deliveryAddress;
        $this->products = $products;
    }
}