<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

/**
 * Класс SearchByProductIdRequest представляет данные для поиска заказов по идентификатору продукта.
 *
 * Этот DTO используется для передачи параметра productId при выполнении запроса на поиск заказов, содержащих указанный продукт.
 *
 * @package App\DTO
 */
#[OA\Schema(
    title: "SearchByProductIdRequest",
    description: "Запрос для поиска заказов, содержащих определенный продукт",
    required: ["productId"]
)]
class SearchByProductIdRequest
{
    /**
     * UUID идентификатор продукта.
     *
     * Обязательный параметр для поиска заказов. Должен быть строкой в формате UUID.
     *
     * @var mixed
     */
    #[Assert\NotBlank(message: "Параметр productId обязателен.")]
    #[Assert\Type(type: "string", message: "Идентификатор товара должен быть строкой.")]
    #[Assert\Uuid(message: "Неверный формат UUID для productId.")]
    #[OA\Property(
        property: "productId",
        description: "UUID идентификатор продукта",
        type: "string",
        format: "uuid",
        example: "0194bd9a-d8b5-7de7-873d-db4907a13836"
    )]
    public mixed $productId;

    /**
     * Конструктор класса SearchByProductIdRequest.
     *
     * @param mixed $productId Идентификатор продукта (UUID), по которому выполняется поиск заказов.
     */
    public function __construct(mixed $productId)
    {
        $this->productId = $productId;
    }
}
