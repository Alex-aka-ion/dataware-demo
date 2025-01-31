<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/orders', name: 'order_')]
class OrderController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly HttpClientInterface $httpClient,
        private readonly OrderRepository $orderRepository
    ) {}

    // Получить списка всех заказов
    #[Route('', methods: ['GET'])]
    public function getOrders(): JsonResponse
    {
        $orders = $this->orderRepository->findAll();

        return $this->json($orders, context: ['groups' => 'order:read']);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Валидация JSON-данных
        if (!$data || !isset($data['deliveryAddress'], $data['products'])) {
            return $this->json(['error' => 'Invalid request data'], Response::HTTP_BAD_REQUEST);
        }

        // Создаём заказ
        $order = new Order();
        $order->setDeliveryAddress($data['deliveryAddress']);

        // Создаём список товаров
        $orderItems = [];
        $allErrors = [];

        foreach ($data['products'] as $key => $productData) {
            $orderItem = new OrderItem();

            if (!isset($productData['productId'])) {
                return $this->json(['error' => "Product ID is missing in item #$key"], Response::HTTP_BAD_REQUEST);
            }

            $productId = $productData['productId'];
            $quantity = (int) $productData['quantity'];

            $orderItem->setProductId($productId);
            $orderItem->setQuantity($quantity);

            $itemErrors = $validator->validate($orderItem, null, ['OrderItem']);
            if (count($itemErrors) > 0) {
                $allErrors = array_merge($allErrors, iterator_to_array($itemErrors));
                continue; // Пропускаем продукт с ошибками
            }

            // Проверяем товар через product-service
            try {
                $productResponse = $this->httpClient->request('GET', "http://product-service/api/products/{$productId}");

                if ($productResponse->getStatusCode() !== 200) {
                    $allErrors[] = $this->json(['error' => "Product {$productId} not found"], Response::HTTP_BAD_REQUEST);
                    continue;
                }

                $productInfo = json_decode($productResponse->getContent(), true);
                $orderItem->setPrice((float) $productInfo['price']);

            } catch (\Exception $e) {
                return $this->json(['error' => 'Product service is unavailable. Please try again later.'], Response::HTTP_SERVICE_UNAVAILABLE);
            }

            $orderItem->setOrder($order);
            $orderItems[] = $orderItem;

            $priceErrors = $validator->validate($orderItem, null, ['StrictValidation']);
            if (count($priceErrors) > 0) {
                $allErrors = array_merge($allErrors, iterator_to_array($priceErrors));
            }
        }

        // Валидация заказа и товаров через Symfony Validator
        $orderErrors = $validator->validate($order);
        if (count($orderErrors) > 0) {
            $allErrors = array_merge($allErrors, iterator_to_array($orderErrors));
        }

        if (!empty($allErrors)) {
            return $this->json($allErrors, Response::HTTP_BAD_REQUEST);
        }

        // Сохраняем заказ и его товары
        $this->entityManager->persist($order);
        foreach ($orderItems as $orderItem) {
            $this->entityManager->persist($orderItem);
        }
        $this->entityManager->flush();

        return $this->json(['message' => 'Order created successfully', 'orderId' => $order->getId()], Response::HTTP_CREATED);
    }

    #[Route('/search', methods: ['GET'])]
    public function searchByProduct(Request $request): JsonResponse
    {
        $productId = $request->query->get('productId');

        if (!$productId) {
            return $this->json(['error' => 'Parameter "productId" is required'], Response::HTTP_BAD_REQUEST);
        }

        $orders = $this->orderRepository->findByProductId($productId);

        return $this->json($orders, context: ['groups' => 'order:read']);
    }

    // Получить заказ по ID
    #[Route('/{id}', requirements: ['id' => '[0-9a-fA-F-]{36}'], methods: ['GET'])]
    public function getOrder(string $id): JsonResponse
    {
        $order = $this->orderRepository->find($id);

        if (!$order) {
            return $this->json(['error' => 'Order not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'id' => $order->getId(),
            'deliveryAddress' => $order->getDeliveryAddress(),
            'createdAt' => $order->getCreatedAt(),
            'orderItems' => array_map(fn($item) => [
                'productId' => $item->getProductId(),
                'quantity' => $item->getQuantity(),
                'price' => $item->getPrice()
            ], $order->getOrderItems()->toArray())
        ]);
    }

    // Обновить заказ (менять можно только адрес доставки)
    #[Route('/{id}', methods: ['PUT'])]
    public function updateOrder(string $id, Request $request): JsonResponse
    {
        $order = $this->orderRepository->find($id);

        if (!$order) {
            return $this->json(['error' => 'Order not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['deliveryAddress'])) {
            return $this->json(['error' => 'Invalid request data'], Response::HTTP_BAD_REQUEST);
        }

        $order->setDeliveryAddress($data['deliveryAddress']);
        $this->entityManager->flush();

        return $this->json(['message' => 'Order updated successfully']);
    }

    // Удалить заказ
    #[Route('/{id}', methods: ['DELETE'])]
    public function deleteOrder(string $id): JsonResponse
    {
        $order = $this->orderRepository->find($id);

        if (!$order) {
            return $this->json(['error' => 'Order not found'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($order);
        $this->entityManager->flush();

        return $this->json(['message' => 'Order deleted successfully'], Response::HTTP_NO_CONTENT);
    }
}
