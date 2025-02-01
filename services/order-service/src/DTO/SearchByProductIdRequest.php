<?php
namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

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
