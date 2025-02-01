<?php
namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="UpdateOrderRequest",
 *     title="Обновление заказа",
 *     description="Запрос для обновления адреса доставки в существующем заказе",
 *     required={"deliveryAddress"},
 *     @OA\Property(
 *         property="deliveryAddress",
 *         type="string",
 *         description="Новый адрес доставки",
 *         example="ул. Пушкина, д. 10, кв. 5",
 *         minLength=5,
 *         maxLength=255
 *     )
 * )
 */
class UpdateOrderRequest
{
    #[Assert\NotBlank(message: "Адрес доставки обязателен.")]
    #[Assert\Length(
        min: 5,
        max: 255,
        minMessage: "Адрес должен содержать не менее 5 символов.",
        maxMessage: "Адрес не может превышать 255 символов."
    )]
    public string $deliveryAddress;

    public function __construct(string $deliveryAddress)
    {
        $this->deliveryAddress = $deliveryAddress;
    }
}
