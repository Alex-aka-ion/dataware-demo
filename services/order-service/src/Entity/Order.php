<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use OpenApi\Attributes as OA;

#[OA\Schema(
    title: "Order",
    description: "Информация о заказе",
    required: ["deliveryAddress", "orderItems"]
)]
#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: "orders")]  // Меняем название таблицы, слово order - ключевое в postgres
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "CUSTOM")]
    #[ORM\Column(type: "uuid", unique: true)]
    #[ORM\CustomIdGenerator(class: "doctrine.uuid_generator")]
    #[Groups(["order:read"])]
    #[OA\Property(
        property: "id",
        description: "Идентификатор заказа",
        type: "string",
        format: "uuid"
    )]
    private ?Uuid $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "Адрес доставки обязателен")]
    #[Assert\Length(
        min: 5,
        max: 255,
        minMessage: "Адрес должен содержать не менее 5 символов",
        maxMessage: "Адрес не может превышать 255 символов"
    )]
    #[Groups(["order:read"])]
    #[OA\Property(
        property: "deliveryAddress",
        description: "Адрес доставки",
        type: "string"
    )]
    private string $deliveryAddress;

    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: "order", cascade: ["persist", "remove"])]
    #[Assert\Valid] // Валидируем вложенные объекты OrderItem
    #[Groups(["order:read"])]
    #[OA\Property(
        property: "orderItems",
        description: "Список товаров в заказе",
        type: "array",
        items: new OA\Items(ref: "#/components/schemas/OrderItem")
    )]
    private Collection $orderItems;

    #[ORM\Column(type: "datetime_immutable")]
    #[Groups(["order:read"])]
    #[OA\Property(
        property: "createdAt",
        description: "Дата создания заказа",
        type: "string",
        format: "date-time"
    )]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->orderItems = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id?->toRfc4122(); // Преобразуем UUID в строку
    }

    public function getDeliveryAddress(): string
    {
        return $this->deliveryAddress;
    }

    public function setDeliveryAddress(string $deliveryAddress): self
    {
        $this->deliveryAddress = $deliveryAddress;
        return $this;
    }

    public function getOrderItems(): Collection
    {
        return $this->orderItems;
    }

    public function addOrderItem(OrderItem $orderItem): self
    {
        if (!$this->orderItems->contains($orderItem)) {
            $this->orderItems->add($orderItem);
            $orderItem->setOrder($this);
        }
        return $this;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt?->format('c');
    }
}