<?php
namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema(
    title: "UpdateOrderRequest",
    description: "Запрос для обновления адреса доставки в существующем заказе",
    required: ["deliveryAddress"]
)]
class UpdateOrderRequest
{
    #[Assert\NotBlank(message: "Адрес доставки обязателен.")]
    #[Assert\Length(
        min: 5,
        max: 255,
        minMessage: "Адрес должен содержать не менее 5 символов.",
        maxMessage: "Адрес не может превышать 255 символов."
    )]
    #[OA\Property(
        property: "deliveryAddress",
        description: "Новый адрес доставки",
        type: "string",
        maxLength: 255,
        minLength: 5,
        example: "ул. Пушкина, д. 10, кв. 5"
    )]
    public mixed $deliveryAddress;

    public function __construct(mixed $deliveryAddress)
    {
        $this->deliveryAddress = $deliveryAddress;
    }
}
