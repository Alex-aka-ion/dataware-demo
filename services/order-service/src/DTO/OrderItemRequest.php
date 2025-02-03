<?php
namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

/**
 * Класс OrderItemRequest представляет данные для добавления товара в заказ.
 *
 * Этот DTO используется для передачи данных при создании или обновлении заказа,
 * включая идентификатор товара и количество.
 */
#[OA\Schema(
    title: "OrderItemRequest",
    description: "Запрос для добавления товара в заказ",
    required: ["productId", "quantity"]
)]
class OrderItemRequest
{
    /**
     * Идентификатор товара (UUID).
     *
     * Поле обязательно для заполнения и должно быть корректным UUID.
     *
     * @var mixed
     */
    #[Assert\NotBlank(message: "Идентификатор товара обязателен.")]
    #[Assert\Type(type: "string", message: "Идентификатор товара должен быть строкой.")]
    #[Assert\Uuid(message: "Неверный формат UUID для идентификатора товара.")]
    #[OA\Property(
        property: "productId",
        description: "Идентификатор товара (UUID)",
        type: "string",
        format: "uuid"
    )]
    public mixed $productId;

    /**
     * Количество товара.
     *
     * Поле обязательно для заполнения и должно быть положительным целым числом.
     *
     * @var mixed
     */
    #[Assert\NotNull(message: "Количество товара обязательно.")]
    #[Assert\Type(type: "integer", message: "Количество товара должно быть целым числом.")]
    #[Assert\Positive(message: "Количество товара должно быть больше 0.")]
    #[OA\Property(
        property: "quantity",
        description: "Количество товара",
        type: "integer",
        minimum: 1
    )]
    public mixed $quantity;

    /**
     * Конструктор класса OrderItemRequest.
     *
     * @param mixed $productId Идентификатор товара (UUID).
     * @param mixed $quantity Количество товара.
     */
    public function __construct(mixed $productId, mixed $quantity)
    {
        $this->productId = $productId;
        $this->quantity = $quantity;
    }
}
