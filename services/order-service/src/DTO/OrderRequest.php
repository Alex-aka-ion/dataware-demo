<?php
namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

/**
 * Класс OrderRequest представляет данные для создания нового заказа.
 *
 * Этот DTO используется для передачи информации о заказе, включая адрес доставки и список товаров.
 *
 * @package App\DTO
 */
#[OA\Schema(
    title: "OrderRequest",
    description: "Данные для создания нового заказа",
    required: ["deliveryAddress", "products"]
)]
class OrderRequest
{
    /**
     * Адрес доставки.
     *
     * Поле обязательно для заполнения и должно быть строкой.
     *
     * @var mixed
     */
    #[Assert\NotBlank(message: "Адрес доставки обязателен.")]
    #[Assert\Type(type: "string", message: "Адрес доставки должен быть строкой.")]
    #[OA\Property(
        property: "deliveryAddress",
        description: "Адрес доставки",
        type: "string",
        example: "123 улица, город, страна"
    )]
    public mixed $deliveryAddress;

    /**
     * Список товаров в заказе.
     *
     * Должен содержать хотя бы один товар. Каждый товар проверяется на валидность.
     *
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
    public mixed $products;

    /**
     * Конструктор класса OrderRequest.
     *
     * @param mixed $deliveryAddress Адрес доставки.
     * @param mixed $products Список товаров в заказе.
     */
    public function __construct(mixed $deliveryAddress, mixed $products)
    {
        $this->deliveryAddress = $deliveryAddress;
        $this->products = $products;
    }
}
