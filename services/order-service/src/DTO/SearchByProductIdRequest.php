<?php
namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema(
    title: "SearchByProductIdRequest",
    description: "Запрос для поиска заказов, содержащих определенный продукт",
    required: ["productId"]
)]
class SearchByProductIdRequest
{
    #[Assert\NotBlank(message: "Параметр productId обязателен.")]
    #[Assert\Uuid(message: "Неверный формат UUID для productId.")]
    #[OA\Property(
        property: "productId",
        description: "UUID идентификатор продукта",
        type: "string",
        format: "uuid",
        example: "0194bd9a-d8b5-7de7-873d-db4907a13836"
    )]
    public string $productId;

    public function __construct(string $productId)
    {
        $this->productId = $productId;
    }
}
