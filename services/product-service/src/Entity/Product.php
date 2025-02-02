<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema(
    title: "Product",
    description: "Модель продукта",
    required: ["name", "price", "categories"]
)]
#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "CUSTOM")]
    #[ORM\Column(type: "uuid", unique: true)]
    #[ORM\CustomIdGenerator(class: "doctrine.uuid_generator")]
    #[OA\Property(
        description: "Уникальный идентификатор продукта",
        type: "string",
        format: "uuid"
    )]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Название не может быть пустым")]
    #[Assert\Length(
        min: 3, minMessage: "Название должно быть не менее 3 символов",
        max: 255, maxMessage: "Название должно быть не более 255 символов"
    )]
    #[OA\Property(
        description: "Название продукта",
        type: "string",
        maxLength: 255,
        example: "Ноутбук ASUS"
    )]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 1000, maxMessage: "Описание не может быть длиннее 1000 символов"
    )]
    #[OA\Property(
        description: "Описание продукта",
        type: "string",
        maxLength: 1000,
        example: "Мощный игровой ноутбук с 16 ГБ оперативной памяти и SSD на 512 ГБ."
    )]
    private ?string $description = null;

    #[ORM\Column(type: "integer")]
    #[Assert\Positive(message: "Цена должна быть положительным числом")]
    #[Assert\LessThanOrEqual(value: 100000000, message: "Цена не может превышать 100 000 000")]
    #[OA\Property(
        description: "Цена продукта",
        type: "number",
        format: "float",
        example: 1499.99
    )]
    private ?int $price = null;

    #[ORM\Column(type: "json")]
    #[Assert\Type(type: "array", message: "Категории должны быть массивом строк")]
    #[Assert\All([
        new Assert\NotBlank(message: "Категория не может быть пустой"),
        new Assert\Length(max: 100, maxMessage: "Категория не может быть длиннее 100 символов")
    ])]
    #[OA\Property(
        description: "Список категорий",
        type: "array",
        items: new OA\Items(type: "string"),
        example: ["Электроника", "Компьютеры"]
    )]
    private array $categories = [];

    #[ORM\Column(type: "datetime_immutable")]
    #[OA\Property(
        description: "Дата создания",
        type: "string",
        format: "date-time",
        example: "2024-05-01T12:34:56Z"
    )]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id?->toRfc4122();
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPrice(): float
    {
        return $this->price / 100;
    }

    public function setPrice(float $price): self
    {
        $this->price = (int) ($price * 100);
        return $this;
    }

    public function getCategories(): array
    {
        return $this->categories;
    }

    public function setCategories(array $categories): static
    {
        $this->categories = $categories;

        return $this;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt?->format('c');
    }
}
