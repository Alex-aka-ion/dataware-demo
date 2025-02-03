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

/**
 * Класс Order представляет сущность заказа в системе.
 *
 * Этот класс управляет данными о заказе, включая адрес доставки,
 * список товаров и дату создания. Он также обеспечивает связь с сущностью OrderItem.
 */
#[OA\Schema(
    title: "Order",
    description: "Информация о заказе",
    required: ["deliveryAddress", "orderItems"]
)]
#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: "orders")]  // Меняем название таблицы, слово order - ключевое в postgres
class Order
{
    /**
     * Уникальный идентификатор заказа в формате UUID.
     *
     * @var Uuid|null
     */
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

    /**
     * Адрес доставки для данного заказа.
     *
     * @var string
     */
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

    /**
     * Список товаров, включённых в данный заказ.
     *
     * @var Collection<int, OrderItem>
     */
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

    /**
     * Дата и время создания заказа.
     *
     * @var \DateTimeImmutable
     */
    #[ORM\Column(type: "datetime_immutable")]
    #[Groups(["order:read"])]
    #[OA\Property(
        property: "createdAt",
        description: "Дата создания заказа",
        type: "string",
        format: "date-time"
    )]
    private \DateTimeImmutable $createdAt;

    /**
     * Конструктор класса Order, инициализирующий коллекцию orderItems и устанавливающий дату создания.
     */
    public function __construct()
    {
        $this->orderItems = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    /**
     * Получить уникальный идентификатор заказа.
     *
     * @return string|null Идентификатор заказа в формате UUID или null, если не установлен.
     */
    public function getId(): ?string
    {
        return $this->id?->toRfc4122();
    }

    /**
     * Получить адрес доставки заказа.
     *
     * @return string Адрес доставки.
     */
    public function getDeliveryAddress(): string
    {
        return $this->deliveryAddress;
    }

    /**
     * Установить адрес доставки для заказа.
     *
     * @param string $deliveryAddress Адрес доставки.
     * @return self Возвращает текущий объект для цепочки вызовов.
     */
    public function setDeliveryAddress(string $deliveryAddress): self
    {
        $this->deliveryAddress = $deliveryAddress;
        return $this;
    }

    /**
     * Получить коллекцию товаров, связанных с заказом.
     *
     * @return Collection<int, OrderItem> Коллекция товаров заказа.
     */
    public function getOrderItems(): Collection
    {
        return $this->orderItems;
    }

    /**
     * Добавить товар в заказ.
     *
     * @param OrderItem $orderItem Товар для добавления в заказ.
     * @return self Возвращает текущий объект для цепочки вызовов.
     */
    public function addOrderItem(OrderItem $orderItem): self
    {
        if (!$this->orderItems->contains($orderItem)) {
            $this->orderItems->add($orderItem);
            $orderItem->setOrder($this);
        }
        return $this;
    }

    /**
     * Получить дату и время создания заказа.
     *
     * @return string Дата и время в формате ISO 8601.
     */
    public function getCreatedAt(): string
    {
        return $this->createdAt?->format('c');
    }
}