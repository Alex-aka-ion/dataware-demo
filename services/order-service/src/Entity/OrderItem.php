<?php
namespace App\Entity;

use App\Repository\OrderItemRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints\GroupSequence;

#[ORM\Entity(repositoryClass: OrderItemRepository::class)]
#[GroupSequence(["OrderItem", "StrictValidation"])] // Группы валидации: сначала "OrderItem", потом "StrictValidation"
class OrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "CUSTOM")]
    #[ORM\Column(type: "uuid", unique: true)]
    #[ORM\CustomIdGenerator(class: "doctrine.uuid_generator")]
    #[Groups(["order:read"])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: "orderItems")]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private Order $order;

    #[ORM\Column(type: "uuid")]
    #[Assert\NotBlank(message: "Идентификатор товара обязателен", groups: ["OrderItem"])]
    #[Assert\Uuid(message: "Неправильный UUID формат для productId", groups: ["OrderItem"])]
    #[Groups(["order:read"])]
    private ?string $productId;

    #[ORM\Column(type: "integer")]
    #[Assert\Positive(message: "Количество должно быть больше 0", groups: ["OrderItem"])]
    #[Groups(["order:read"])]
    private ?int $quantity;

    #[ORM\Column(type: "integer")]
    #[Assert\Positive(message: "Цена должна быть положительным числом", groups: ["StrictValidation"])]
    #[Groups(["order:read"])]
    private ?int $price; // Цена в момент заказа

    public function getId(): ?string
    {
        return $this->id?->toRfc4122(); // Преобразуем UUID в строку для JSON
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function setOrder(Order $order): self
    {
        $this->order = $order;
        return $this;
    }

    public function getProductId(): ?string
    {
        return $this->productId;
    }

    public function setProductId(?string $productId): self
    {
        $this->productId = $productId;
        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(?int $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getPrice(): float
    {
        return $this->price / 100;
    }

    public function setPrice(?float $price): self
    {
        $this->price = (int) ($price * 100);
        return $this;
    }
}
