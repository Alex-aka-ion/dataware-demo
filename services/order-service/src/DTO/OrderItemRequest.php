<?php
namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

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
