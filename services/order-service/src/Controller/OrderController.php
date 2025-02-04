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
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

/**
 * Контроллер для управления заказами.
 *
 * Предоставляет функциональность для создания, получения, обновления и удаления заказов.
 */
#[Route('/api/orders', name: 'order_')]
class OrderController extends AbstractController
{
    /**
     * @param EntityManagerInterface $entityManager Менеджер сущностей Doctrine для работы с БД.
     * @param HttpClientInterface $httpClient HTTP-клиент для взаимодействия с внешними сервисами.
     * @param OrderRepository $orderRepository Репозиторий для работы с заказами.
     * @param LoggerInterface $logger Объект логирования
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly HttpClientInterface    $httpClient,
        private readonly OrderRepository        $orderRepository,
        private readonly LoggerInterface        $logger
    )
    {
    }

    /**
     * Получить список всех заказов.
     *
     * @return JsonResponse Список заказов в формате JSON.
     */
    #[OA\Get(
        path: '/api/orders',
        summary: 'Получить список всех заказов',
        tags: ['Orders'],
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

    /**
     * Создать новый заказ.
     *
     * @param Request $request HTTP-запрос с данными заказа.
     * @param ValidatorInterface $validator Валидатор для проверки данных.
     * @return JsonResponse Результат создания заказа.
     */
    #[OA\Post(
        path: '/api/orders',
        summary: 'Создать новый заказ',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/OrderRequest')
        ),
        tags: ['Orders'],
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
            new OA\Response(
                response: 400,
                description: 'Ошибка валидации или некорректный формат данных',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Некорректный формат JSON'),
                        new OA\Property(property: 'errors', type: 'array', items: new OA\Items(type: 'string'))
                    ]
                )
            ),
            new OA\Response(
                response: 502,
                description: 'Ошибка перенаправления (Redirection error)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Product-service недоступен. Redirection error')
                    ]
                )
            ),
            new OA\Response(
                response: 503,
                description: 'Ошибка транспортного уровня (Transport error)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Product-service недоступен. Transport error')
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Внутренняя ошибка сервера (Server error или Unexpected error)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Product-service недоступен. Server error')
                    ]
                )
            )
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
            $quantity = (int)$productData['quantity'];

            $orderItem->setProductId($productId);
            $orderItem->setQuantity($quantity);

            // Проверяем наличие и цену товар через product-service
            try {
                $url = "http://product-service/api/products/{$productId}";

                $productResponse = $this->httpClient->request('GET', $url);

                if ($productResponse->getStatusCode() === Response::HTTP_NOT_FOUND) {
                    $allErrors[] = $this->json(['error' => "Продукт с ID {$productId} не найден в product-service"], Response::HTTP_BAD_REQUEST);
                    continue;
                }
                if ($productResponse->getStatusCode() !== Response::HTTP_OK) {
                    throw new \Exception('Product-service вернул неожиданный код: ' . $productResponse->getStatusCode());
                }

                $productInfo = json_decode($productResponse->getContent(), true);
                $orderItem->setPrice((float)$productInfo['price']);
            } catch (ClientExceptionInterface $e) {
                $this->logger->error('Client error occurred', ['message' => $e->getMessage(), 'url' => $url]);
                return new JsonResponse(['error' => 'Product-service недоступен. Client error'], Response::HTTP_BAD_REQUEST);
            } catch (ServerExceptionInterface $e) {
                $this->logger->error('Server error occurred', ['message' => $e->getMessage(), 'url' => $url]);
                return new JsonResponse(['error' => 'Product-service недоступен. Server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
            } catch (RedirectionExceptionInterface $e) {
                $this->logger->error('Redirection error occurred', ['message' => $e->getMessage(), 'url' => $url]);
                return new JsonResponse(['error' => 'Product-service недоступен. Redirection error'], Response::HTTP_BAD_GATEWAY);
            } catch (TransportExceptionInterface $e) {
                $this->logger->error('Transport error occurred', ['message' => $e->getMessage(), 'url' => $url]);
                return new JsonResponse(['error' => 'Product-service недоступен. Transport error'], Response::HTTP_SERVICE_UNAVAILABLE);
            } catch (\Exception $e) {
                $this->logger->error('Unexpected error occurred', ['message' => $e->getMessage(), 'url' => $url]);
                return new JsonResponse(['error' => 'Product-service недоступен. Unexpected error'], Response::HTTP_INTERNAL_SERVER_ERROR);
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

    /**
     * Найти заказы по ID продукта.
     *
     * @param Request $request HTTP-запрос с параметром productId.
     * @param ValidatorInterface $validator Валидатор для проверки ID продукта.
     * @return JsonResponse Список найденных заказов.
     */
    #[OA\Get(
        path: '/api/orders/search',
        summary: 'Найти заказы по ID товара',
        tags: ['Orders'],
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

    /**
     * Получить заказ по его ID.
     *
     * @param string $id Идентификатор заказа.
     * @return JsonResponse Информация о заказе или ошибка, если не найден.
     */
    #[OA\Get(
        path: '/api/orders/{id}',
        summary: 'Получить заказ по ID',
        tags: ['Orders'],
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

    /**
     * Обновить адрес доставки для существующего заказа.
     *
     * @param string $id Идентификатор заказа.
     * @param Request $request HTTP-запрос с новыми данными.
     * @param ValidatorInterface $validator Валидатор для проверки данных.
     * @return JsonResponse Результат обновления заказа.
     */
    #[OA\Put(
        path: '/api/orders/{id}',
        summary: 'Обновить адрес доставки',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateOrderRequest')
        ),
        tags: ['Orders'],
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
            return $this->json(['errors' => (string)$errors], Response::HTTP_BAD_REQUEST);
        }

        $order->setDeliveryAddress(strip_tags($updateOrderRequest->deliveryAddress));
        $this->entityManager->flush();

        return $this->json(['message' => 'Адрес доставки успешно обновлен']);
    }

    /**
     * Удалить заказ по его ID.
     *
     * @param string $id Идентификатор заказа.
     * @return JsonResponse Результат удаления заказа.
     */
    #[OA\Delete(
        path: '/api/orders/{id}',
        summary: 'Удалить заказ',
        tags: ['Orders'],
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
