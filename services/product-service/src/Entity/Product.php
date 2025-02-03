<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

/**
 * Класс Product представляет собой модель продукта для системы управления товарами.
 *
 * Этот класс описывает основные свойства продукта, включая его уникальный идентификатор, название, описание, цену,
 * категории и дату создания. Он также содержит геттеры и сеттеры для управления данными продукта.
 */
#[OA\Schema(
    title: "Product",
    description: "Модель продукта",
    required: ["name", "price", "categories"]
)]
#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    /**
     * Уникальный идентификатор продукта (UUID).
     *
     * @var Uuid|null Идентификатор продукта в формате UUID.
     */
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

    /**
     * Название продукта.
     *
     * @var string|null Название продукта длиной от 3 до 255 символов.
     */
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Название не может быть пустым")]
    #[Assert\Length(
        min: 3, max: 255,
        minMessage: "Название должно быть не менее 3 символов", maxMessage: "Название должно быть не более 255 символов"
    )]
    #[OA\Property(
        description: "Название продукта",
        type: "string",
        maxLength: 255,
        example: "Ноутбук ASUS"
    )]
    private ?string $name = null;

    /**
     * Описание продукта (необязательное поле).
     *
     * @var string|null Описание продукта, длиной до 1000 символов.
     */
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

    /**
     * Цена продукта в копейках (сохраняется как целое число).
     *
     * @var int|null Цена продукта. Должна быть положительным числом и не превышать 100 000 000.
     */
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

    /**
     * Список категорий, к которым относится продукт.
     *
     * @var array Массив строк, каждая из которых представляет категорию.
     */
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

    /**
     * Дата создания продукта.
     *
     * @var \DateTimeImmutable|null Дата и время создания продукта в формате ISO 8601.
     */
    #[ORM\Column(type: "datetime_immutable")]
    #[OA\Property(
        description: "Дата создания",
        type: "string",
        format: "date-time",
        example: "2024-05-01T12:34:56Z"
    )]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * Конструктор класса Product.
     *
     * Инициализирует дату создания текущим моментом времени.
     */
    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    /**
     * Получить идентификатор продукта в формате UUID.
     *
     * @return string|null Идентификатор продукта.
     */
    public function getId(): ?string
    {
        return $this->id?->toRfc4122();
    }

    /**
     * Получить название продукта.
     *
     * @return string|null Название продукта.
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Установить название продукта.
     *
     * @param string $name Название продукта.
     * @return static Возвращает текущий объект для цепочки вызовов.
     */
    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Получить описание продукта.
     *
     * @return string|null Описание продукта.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Установить описание продукта.
     *
     * @param string|null $description Описание продукта.
     * @return static Возвращает текущий объект для цепочки вызовов.
     */
    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Получить цену продукта в формате с плавающей точкой.
     *
     * @return float Цена продукта.
     */
    public function getPrice(): float
    {
        return $this->price / 100;
    }

    /**
     * Установить цену продукта (в рублях).
     *
     * @param float $price Цена продукта.
     * @return self Возвращает текущий объект для цепочки вызовов.
     */
    public function setPrice(float $price): self
    {
        $this->price = (int) ($price * 100);
        return $this;
    }

    /**
     * Получить список категорий продукта.
     *
     * @return array Список категорий продукта.
     */
    public function getCategories(): array
    {
        return $this->categories;
    }

    /**
     * Установить категории продукта.
     *
     * @param array $categories Список категорий продукта.
     * @return static Возвращает текущий объект для цепочки вызовов.
     */
    public function setCategories(array $categories): static
    {
        $this->categories = $categories;
        return $this;
    }

    /**
     * Получить дату создания продукта в формате ISO 8601.
     *
     * @return string Дата создания продукта.
     */
    public function getCreatedAt(): string
    {
        return $this->createdAt?->format('c');
    }
}
