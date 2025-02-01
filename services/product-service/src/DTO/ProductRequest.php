<?php
namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class ProductRequest
{
    #[Assert\NotBlank(message: "Название продукта обязательно.")]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: "Название продукта должно содержать минимум 3 символа.",
        maxMessage: "Название продукта не может превышать 255 символов."
    )]
    public string $name;

    #[Assert\Length(
        max: 1000,
        maxMessage: "Описание не может превышать 1000 символов."
    )]
    public ?string $description = null;

    #[Assert\NotNull(message: "Цена продукта обязательна.")]
    #[Assert\Positive(message: "Цена должна быть положительным числом.")]
    public float $price;

    #[Assert\NotNull(message: "Категории обязательны.")]
    #[Assert\Type(type: "array", message: "Категории должны быть массивом.")]
    #[Assert\Count(min: 1, minMessage: "Необходимо указать хотя бы одну категорию.")]
    public array $categories;

    public function __construct(string $name, float $price, array $categories, ?string $description = null)
    {
        $this->name = $name;
        $this->price = $price;
        $this->categories = $categories;
        $this->description = $description;
    }
}

