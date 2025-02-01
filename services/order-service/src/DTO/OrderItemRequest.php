<?php
namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="OrderItemRequest",
 *     title="Элемент заказа (запрос)",
 *     description="Запрос для добавления товара в заказ",
 *     required={"productId", "quantity"},
 *     @OA\Property(
 *         property="productId",
 *         type="string",
 *         format="uuid",
 *         description="Идентификатор товара (UUID)"
 *     ),
 *     @OA\Property(
 *         property="quantity",
 *         type="integer",
 *         description="Количество товара",
 *         minimum=1
 *     )
 * )
 */
class OrderItemRequest
{
    #[Assert\NotBlank(message: "Идентификатор товара обязателен.")]
    #[Assert\Uuid(message: "Неверный формат UUID для идентификатора товара.")]
    public string $productId;

    #[Assert\NotNull(message: "Количество товара обязательно.")]
    #[Assert\Positive(message: "Количество товара должно быть больше 0.")]
    public int $quantity;

    public function __construct(string $productId, int $quantity)
    {
        $this->productId = $productId;
        $this->quantity = $quantity;
    }
}
