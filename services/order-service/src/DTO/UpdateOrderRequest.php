<?php
namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

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
