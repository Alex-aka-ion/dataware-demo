<?php
namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="OrderRequest",
 *     title="Запрос на создание заказа",
 *     description="Данные для создания нового заказа",
 *     required={"deliveryAddress", "products"},
 *     @OA\Property(
 *         property="deliveryAddress",
 *         type="string",
 *         description="Адрес доставки",
 *         example="123 улица, город, страна"
 *     ),
 *     @OA\Property(
 *         property="products",
 *         type="array",
 *         description="Список товаров в заказе",
 *         @OA\Items(ref="#/components/schemas/OrderItemRequest")
 *     )
 * )
 */
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