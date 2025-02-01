<?php
namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="ProductRequest",
 *     required={"name", "price", "categories"},
 *     @OA\Property(property="name", type="string", minLength=3, maxLength=255, example="Ноутбук ASUS"),
 *     @OA\Property(property="description", type="string", maxLength=1000, example="Мощный игровой ноутбук."),
 *     @OA\Property(property="price", type="number", format="float", minimum=0, example=1499.99),
 *     @OA\Property(
 *         property="categories",
 *         type="array",
 *         @OA\Items(type="string", example="Электроника"),
 *         example={"Электроника", "Компьютеры"}
 *     )
 * )
 */
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
    #[Assert\LessThanOrEqual(value: 100000000, message: "Цена не может превышать 100 000 000")]
    public float $price;

    #[Assert\NotNull(message: "Категории обязательны.")]
    #[Assert\Type(type: "array", message: "Категории должны быть массивом.")]
    #[Assert\Count(min: 1, minMessage: "Необходимо указать хотя бы одну категорию.")]
    #[Assert\All([
        new Assert\NotBlank(message: "Категория не может быть пустой"),
        new Assert\Length(max: 100, maxMessage: "Категория не может быть длиннее 100 символов")
    ])]
    public array $categories;

    public function __construct(string $name, float $price, array $categories, ?string $description = null)
    {
        $this->name = $name;
        $this->price = $price;
        $this->categories = $categories;
        $this->description = $description;
    }
}

