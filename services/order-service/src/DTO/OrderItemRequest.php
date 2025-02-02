<?php
namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema(
    title: "OrderItemRequest",
    description: "Запрос для добавления товара в заказ",
    required: ["productId", "quantity"]
)]
class OrderItemRequest
{
    #[Assert\NotBlank(message: "Идентификатор товара обязателен.")]
    #[Assert\Uuid(message: "Неверный формат UUID для идентификатора товара.")]
    #[OA\Property(
        property: "productId",
        description: "Идентификатор товара (UUID)",
        type: "string",
        format: "uuid"
    )]
    public string $productId;

    #[Assert\NotNull(message: "Количество товара обязательно.")]
    #[Assert\Positive(message: "Количество товара должно быть больше 0.")]
    #[OA\Property(
        property: "quantity",
        description: "Количество товара",
        type: "integer",
        minimum: 1
    )]
    public int $quantity;

    public function __construct(string $productId, int $quantity)
    {
        $this->productId = $productId;
        $this->quantity = $quantity;
    }
}
