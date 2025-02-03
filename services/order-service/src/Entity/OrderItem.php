<?php
namespace App\Entity;

use App\Repository\OrderItemRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints\GroupSequence;
use OpenApi\Attributes as OA;

/**
 * Класс OrderItem представляет товар в составе заказа.
 *
 * Этот класс управляет данными о товаре, связанном с заказом, включая:
 * - Идентификатор заказа
 * - Идентификатор товара (UUID)
 * - Количество товара
 * - Цена товара на момент оформления заказа
 *
 * Валидация данных осуществляется с использованием групп "OrderItem" и "StrictValidation".
 *
 * @package App\Entity
 */
#[ORM\Entity(repositoryClass: OrderItemRepository::class)]
#[GroupSequence(["OrderItem", "StrictValidation"])] // Группы валидации: сначала "OrderItem", потом "StrictValidation"
#[OA\Schema(
    title: "OrderItem",
    description: "Информация о товаре в заказе",
    required: ["id", "productId", "quantity", "price"]
)]
class OrderItem
{
    /**
     * Уникальный идентификатор элемента заказа (UUID).
     *
     * @var Uuid|null
     */
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "CUSTOM")]
    #[ORM\Column(type: "uuid", unique: true)]
    #[ORM\CustomIdGenerator(class: "doctrine.uuid_generator")]
    #[Groups(["order:read"])]
    #[OA\Property(description: "Идентификатор элемента заказа", type: "string", format: "uuid")]
    private ?Uuid $id = null;

    /**
     * Ссылка на заказ, к которому относится товар.
     *
     * @var Order
     */
    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: "orderItems")]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private Order $order;

    /**
     * UUID идентификатор товара.
     *
     * @var string
     */
    #[ORM\Column(type: "uuid")]
    #[Assert\NotBlank(message: "Идентификатор товара обязателен", groups: ["OrderItem"])]
    #[Assert\Uuid(message: "Неправильный UUID формат для productId", groups: ["OrderItem"])]
    #[Groups(["order:read"])]
    #[OA\Property(description: "UUID идентификатор товара", type: "string", format: "uuid")]
    private string $productId;

    /**
     * Количество товара в заказе.
     *
     * @var int
     */
    #[ORM\Column(type: "integer")]
    #[Assert\Positive(message: "Количество должно быть больше 0", groups: ["OrderItem"])]
    #[Groups(["order:read"])]
    #[OA\Property(description: "Количество товара", type: "integer", minimum: 1)]
    private int $quantity;

    /**
     * Цена товара в момент оформления заказа (в копейках).
     *
     * Для хранения используется целое число для избежания ошибок округления.
     * При выводе делится на 100 для получения значения в рублях.
     *
     * @var int
     */
    #[ORM\Column(type: "integer")]
    #[Assert\Positive(message: "Цена должна быть положительным числом", groups: ["StrictValidation"])]
    #[Groups(["order:read"])]
    #[OA\Property(description: "Цена товара в момент заказа", type: "number", format: "float")]
    private int $price; // Цена в момент заказа

    /**
     * Получить идентификатор элемента заказа в формате UUID.
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id?->toRfc4122(); // Преобразуем UUID в строку для JSON
    }

    /**
     * Получить заказ, к которому относится товар.
     *
     * @return Order
     */
    public function getOrder(): Order
    {
        return $this->order;
    }

    /**
     * Установить заказ, к которому относится товар.
     *
     * @param Order $order
     * @return $this
     */
    public function setOrder(Order $order): self
    {
        $this->order = $order;
        return $this;
    }

    /**
     * Получить UUID идентификатор товара.
     *
     * @return string
     */
    public function getProductId(): string
    {
        return $this->productId;
    }

    /**
     * Установить UUID идентификатор товара.
     *
     * @param string $productId
     * @return $this
     */
    public function setProductId(string $productId): self
    {
        $this->productId = $productId;
        return $this;
    }

    /**
     * Получить количество товара в заказе.
     *
     * @return int
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * Установить количество товара в заказе.
     *
     * @param int $quantity
     * @return $this
     */
    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }

    /**
     * Получить цену товара в момент заказа (в рублях).
     *
     * @return float
     */
    public function getPrice(): float
    {
        return $this->price / 100;
    }

    /**
     * Установить цену товара в момент заказа (в рублях).
     *
     * @param float $price
     * @return $this
     */
    public function setPrice(float $price): self
    {
        $this->price = (int) ($price * 100);
        return $this;
    }
}
