<?php
namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

/**
 * Класс UpdateOrderRequest представляет данные для обновления адреса доставки в существующем заказе.
 *
 * Этот DTO используется для передачи нового адреса доставки при обновлении информации о заказе.
 */
#[OA\Schema(
    title: "UpdateOrderRequest",
    description: "Запрос для обновления адреса доставки в существующем заказе",
    required: ["deliveryAddress"]
)]
class UpdateOrderRequest
{
    /**
     * Новый адрес доставки.
     *
     * Этот параметр обязателен и должен содержать от 5 до 255 символов.
     * Используется для обновления адреса доставки в заказе.
     *
     * @var mixed
     */
    #[Assert\NotBlank(message: "Адрес доставки обязателен.")]
    #[Assert\Type(type: "string", message: "Адрес доставки должен быть строкой.")]
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

    /**
     * Конструктор класса UpdateOrderRequest.
     *
     * @param mixed $deliveryAddress Новый адрес доставки, который нужно установить для заказа.
     */
    public function __construct(mixed $deliveryAddress)
    {
        $this->deliveryAddress = $deliveryAddress;
    }
}
