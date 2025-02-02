<?php

namespace App\Controller;

use App\DTO\OrderItemRequest;
use App\DTO\OrderRequest;
use App\DTO\SearchByProductIdRequest;
use App\DTO\UpdateOrderRequest;
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
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

#[Route('/api/orders', name: 'order_')]
class OrderController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly HttpClientInterface $httpClient,
        private readonly OrderRepository $orderRepository
    ) {}

    #[OA\Get(
        path: '/api/orders',
        summary: 'Получить список всех заказов',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Список заказов',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Order')
                )
            )
        ]
    )]
    #[Route('', methods: ['GET'])]
    public function getOrders(): JsonResponse
    {
        $orders = $this->orderRepository->findAll();

        return $this->json($orders, context: ['groups' => 'order:read']);
    }

    #[OA\Post(
        path: '/api/orders',
        summary: 'Создать новый заказ',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/OrderRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Заказ создан успешно',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Заказ создан успешно'),
                        new OA\Property(property: 'orderId', type: 'string', format: 'uuid')
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Ошибка валидации')
        ]
    )]
    #[Route('', methods: ['POST'])]
    public function create(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['error' => 'Некорректный формат JSON'], Response::HTTP_BAD_REQUEST);
        }

        $products = array_map(
            fn($product) => new OrderItemRequest($product['productId'] ?? null, $product['quantity'] ?? null),
            $data['products'] ?? []
        );

        $orderRequest = new OrderRequest($data['deliveryAddress'] ?? null, $products);

        // Валидация данных DTO
        $errors = $validator->validate($orderRequest);
        if (count($errors) > 0) {
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        // Создаём заказ
        $order = new Order();
        $order->setDeliveryAddress(strip_tags($data['deliveryAddress']));

        // Создаём список товаров
        $orderItems = [];
        $allErrors = [];

        foreach ($data['products'] as $productData) {
            $orderItem = new OrderItem();

            $productId = $productData['productId'];
            $quantity = (int) $productData['quantity'];

            $orderItem->setProductId($productId);
            $orderItem->setQuantity($quantity);

            // Проверяем наличие и цену товар через product-service
            try {
                $productResponse = $this->httpClient->request('GET', "http://product-service/api/products/{$productId}");

                if ($productResponse->getStatusCode() !== 200) {
                    $allErrors[] = $this->json(['error' => "Продукт с ID {$productId} не найден в product-service"], Response::HTTP_BAD_REQUEST);
                    continue;
                }

                $productInfo = json_decode($productResponse->getContent(), true);
                $orderItem->setPrice((float) $productInfo['price']);

            } catch (\Exception $e) {
                return $this->json(['error' => 'Product-service недоступен. Пожалуйста, попробуйте позже.'], Response::HTTP_SERVICE_UNAVAILABLE);
            }

            $orderItem->setOrder($order);
            $orderItems[] = $orderItem;

            $priceErrors = $validator->validate($orderItem, null, ['StrictValidation']); // Тут проверяем цену товара
            if (count($priceErrors) > 0) {
                $allErrors = array_merge($allErrors, iterator_to_array($priceErrors));
            }
        }

        // Полная валидация заказа и товаров
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

        return $this->json(['message' => 'Заказ создан успешно', 'orderId' => $order->getId()], Response::HTTP_CREATED);
    }

    #[OA\Get(
        path: '/api/orders/search',
        summary: 'Найти заказы по ID товара',
        parameters: [
            new OA\Parameter(
                name: 'productId',
                description: 'Идентификатор продукта',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Список заказов',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Order')
                )
            ),
            new OA\Response(response: 400, description: 'Некорректный запрос'),
            new OA\Response(response: 404, description: 'Заказы не найдены')
        ]
    )]
    #[Route('/search', methods: ['GET'])]
    public function searchByProductId(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $productId = $request->query->get('productId');

        $searchRequest = new SearchByProductIdRequest($productId ?? '');

        $errors = $validator->validate($searchRequest);
        if (count($errors) > 0) {
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $orders = $this->orderRepository->findByProductId($productId);

        return $this->json($orders, context: ['groups' => 'order:read']);
    }

    #[OA\Get(
        path: '/api/orders/{id}',
        summary: 'Получить заказ по ID',
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Информация о заказе',
                content: new OA\JsonContent(ref: '#/components/schemas/Order')
            ),
            new OA\Response(response: 404, description: 'Заказ не найден')
        ]
    )]
    #[Route('/{id}', methods: ['GET'])]
    public function getOrder(string $id): JsonResponse
    {
        $order = $this->orderRepository->find($id);

        if (!$order) {
            return $this->json(['error' => 'Заказ не найден'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($order, context: ['groups' => 'order:read']);
    }

    #[OA\Put(
        path: '/api/orders/{id}',
        summary: 'Обновить адрес доставки',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateOrderRequest')
        ),
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Адрес доставки успешно обновлен',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Адрес доставки успешно обновлен')
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Заказ не найден'),
            new OA\Response(response: 400, description: 'Ошибка валидации')
        ]
    )]
    #[Route('/{id}', methods: ['PUT'])]
    public function updateOrder(string $id, Request $request, ValidatorInterface $validator): JsonResponse
    {
        $order = $this->orderRepository->find($id);

        if (!$order) {
            return $this->json(['error' => 'Заказ не найден'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['error' => 'Некорректный формат JSON'], Response::HTTP_BAD_REQUEST);
        }

        $updateOrderRequest = new UpdateOrderRequest(
            $data['deliveryAddress'] ?? ''
        );

        $errors = $validator->validate($updateOrderRequest);
        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $order->setDeliveryAddress(strip_tags($updateOrderRequest->deliveryAddress));
        $this->entityManager->flush();

        return $this->json(['message' => 'Адрес доставки успешно обновлен']);
    }

    #[OA\Delete(
        path: '/api/orders/{id}',
        summary: 'Удалить заказ',
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            )
        ],
        responses: [
            new OA\Response(response: 204, description: 'Заказ удален успешно'),
            new OA\Response(response: 404, description: 'Заказ не найден')
        ]
    )]
    #[Route('/{id}', methods: ['DELETE'])]
    public function deleteOrder(string $id): JsonResponse
    {
        $order = $this->orderRepository->find($id);

        if (!$order) {
            return $this->json(['error' => 'Заказ не найден'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($order);
        $this->entityManager->flush();

        return $this->json(['message' => 'Заказ удален успешно'], Response::HTTP_NO_CONTENT);
    }
}
