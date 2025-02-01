<?php
namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="SearchByProductIdRequest",
 *     title="Поиск заказов по productId",
 *     description="Запрос для поиска заказов, содержащих определенный продукт",
 *     required={"productId"},
 *     @OA\Property(
 *         property="productId",
 *         type="string",
 *         format="uuid",
 *         description="UUID идентификатор продукта",
 *         example="0194bd9a-d8b5-7de7-873d-db4907a13836"
 *     )
 * )
 */
class SearchByProductIdRequest
{
    #[Assert\NotBlank(message: "Параметр productId обязателен.")]
    #[Assert\Uuid(message: "Неверный формат UUID для productId.")]
    public string $productId;

    public function __construct(string $productId)
    {
        $this->productId = $productId;
    }
}
