<?php
namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema(
    title: "ProductRequest",
    description: "Запрос для создания или обновления продукта",
    required: ["name", "price", "categories"]
)]
class ProductRequest
{
    #[Assert\NotBlank(message: "Название продукта обязательно.")]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: "Название продукта должно содержать минимум 3 символа.",
        maxMessage: "Название продукта не может превышать 255 символов."
    )]
    #[OA\Property(
        description: "Название продукта",
        type: "string",
        maxLength: 255,
        minLength: 3,
        example: "Ноутбук ASUS"
    )]
    public mixed $name;

    #[Assert\Length(
        max: 1000,
        maxMessage: "Описание не может превышать 1000 символов."
    )]
    #[OA\Property(
        description: "Описание продукта",
        type: "string",
        maxLength: 1000,
        example: "Мощный игровой ноутбук."
    )]
    public mixed $description = null;

    #[Assert\NotNull(message: "Цена продукта обязательна.")]
    #[Assert\Positive(message: "Цена должна быть положительным числом.")]
    #[Assert\LessThanOrEqual(value: 100000000, message: "Цена не может превышать 100 000 000")]
    #[OA\Property(
        description: "Цена продукта",
        type: "number",
        format: "float",
        minimum: 0,
        example: 1499.99
    )]
    public mixed $price;

    #[Assert\NotNull(message: "Категории обязательны.")]
    #[Assert\Type(type: "array", message: "Категории должны быть массивом.")]
    #[Assert\Count(min: 1, minMessage: "Необходимо указать хотя бы одну категорию.")]
    #[Assert\All([
        new Assert\Type(type: "string", message: "Каждая категория должна быть строкой."),
        new Assert\NotBlank(message: "Категория не может быть пустой"),
        new Assert\Length(max: 100, maxMessage: "Категория не может быть длиннее 100 символов")
    ])]
    #[OA\Property(
        description: "Список категорий продукта",
        type: "array",
        items: new OA\Items(type: "string", example: "Электроника"),
        example: ["Электроника", "Компьютеры"]
    )]
    public mixed $categories;

    public function __construct(mixed $name, mixed $price, mixed $categories, mixed $description = null)
    {
        $this->name = $name;
        $this->price = $price;
        $this->categories = $categories;
        $this->description = $description;
    }
}

