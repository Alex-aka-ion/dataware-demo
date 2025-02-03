<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use OpenApi\Attributes as OA;

/**
 * Контроллер API-шлюза для маршрутизации запросов к микросервисам продуктов и заказов.
 *
 * Этот контроллер обрабатывает HTTP-запросы и проксирует их в соответствующие сервисы:
 * - **Product Service**: операции с продуктами (создание, получение, обновление, удаление).
 * - **Order Service**: операции с заказами (создание, получение, обновление, удаление).
 *
 * Контроллер также ограничивает доступ к определённым маршрутам и методам в соответствии с настройками разрешений.
 *
 * @package App\Controller
 */

#[AsController]
#[Route('/api')]
readonly class GatewayController
{
    /**
     * Разрешённые маршруты и методы для каждого сервиса.
     *
     * @var array<string, array<string, string[]>>
     */
    private array $allowedRoutes;

    /**
     * Конструктор GatewayController.
     *
     * @param HttpClientInterface $httpClient HTTP-клиент для отправки проксируемых запросов.
     */
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger
    )
    {
        $this->allowedRoutes = [
            'product-service' => [
                '/api/products' => ['GET', 'POST'],
                '/api/products/{id}' => ['GET', 'PUT', 'DELETE'],
                '/api/products/search' => ['GET'],
            ],
            'order-service' => [
                '/api/orders' => ['GET', 'POST'],
                '/api/orders/{id}' => ['GET', 'PUT', 'DELETE'],
                '/api/orders/search' => ['GET'],
            ],
        ];
    }

    /**
     * Проверяет, разрешён ли маршрут для определённого сервиса и метода.
     *
     * @param string $service Название сервиса (например, 'product-service').
     * @param string $path Путь запроса.
     * @param string $method HTTP-метод запроса (GET, POST и т.д.).
     *
     * @return bool Возвращает true, если маршрут разрешён, иначе false.
     */
    private function isRouteAllowed(string $service, string $path, string $method): bool
    {
        foreach ($this->allowedRoutes[$service] as $route => $methods) {
            $pattern = preg_replace('/\{[^}]+}/', '[^/]+', $route);
            if (preg_match("#^{$pattern}$#", $path) && in_array($method, $methods)) {
                return true;
            }
        }
        return false;
    }

    #[OA\Get(
        path: '/api/products',
        summary: 'Получить список всех продуктов',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Список продуктов',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '0194bd9a-d8b5-7de7-873d-db4907a13836'),
                            new OA\Property(property: 'name', type: 'string', example: 'Ноутбук ASUS'),
                            new OA\Property(property: 'description', type: 'string', example: 'Мощный игровой ноутбук.'),
                            new OA\Property(property: 'price', type: 'number', format: 'float', example: 1499.99),
                            new OA\Property(
                                property: 'categories',
                                type: 'array',
                                items: new OA\Items(type: 'string', example: 'Электроника')
                            ),
                            new OA\Property(property: 'createdAt', type: 'string', format: 'date-time', example: '2024-05-01T12:34:56Z')
                        ],
                        type: 'object'
                    )
                )
            )
        ]
    )]
    #[OA\Post(
        path: '/api/products',
        summary: 'Создать новый продукт',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'price', 'categories'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Ноутбук Lenovo'),
                    new OA\Property(property: 'description', type: 'string', example: 'Ультрабук с SSD на 512 ГБ.'),
                    new OA\Property(property: 'price', type: 'number', format: 'float', example: 999.99),
                    new OA\Property(
                        property: 'categories',
                        type: 'array',
                        items: new OA\Items(type: 'string', example: 'Компьютеры')
                    )
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Продукт создан',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '0194bd9a-d8b5-7de7-873d-db4907a13836'),
                        new OA\Property(property: 'message', type: 'string', example: 'Продукт успешно создан')
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 400, description: 'Ошибка валидации')
        ]
    )]
    #[Route('/products', name: 'proxy_product_list', methods: ['GET', 'POST'])]
    public function proxyProductList(Request $request): JsonResponse
    {
        return $this->proxyProductService($request);
    }

    #[OA\Get(
        path: "/api/products/{id}",
        summary: "Получить продукт по ID",
        tags: ["Product Service"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "ID продукта",
                in: "path",
                required: false,
                schema: new OA\Schema(type: "string", format: "uuid")
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Успешный ответ"),
            new OA\Response(response: 403, description: "Доступ запрещён"),
            new OA\Response(response: 502, description: "Ошибка сервиса")
        ]
    )]
    #[OA\Put(
        path: "/api/products/{id}",
        summary: "Обновить данные продукта",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent()
        ),
        tags: ["Product Service"],
        responses: [
            new OA\Response(response: 200, description: "Данные обновлены"),
            new OA\Response(response: 404, description: "Продукт не найден")
        ]
    )]
    #[OA\Delete(
        path: "/api/products/{id}",
        summary: "Удалить продукт",
        tags: ["Product Service"],
        responses: [
            new OA\Response(response: 204, description: "Продукт удалён"),
            new OA\Response(response: 404, description: "Продукт не найден")
        ]
    )]
    #[Route('/products/{id}', name: 'proxy_product_detail', methods: ['GET', 'PUT', 'DELETE'])]
    public function proxyProductDetail(Request $request, string $id): JsonResponse
    {
        return $this->proxyProductService($request, $id);
    }

    #[OA\Get(
        path: "/api/products/search",
        summary: "Поиск продуктов по имени",
        tags: ["Product Service"],
        parameters: [
            new OA\Parameter(
                name: "name",
                description: "Имя продукта для поиска",
                in: "query",
                required: true,
                schema: new OA\Schema(type: "string")
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Результаты поиска", content: new OA\JsonContent(type: "array", items: new OA\Items())),
            new OA\Response(response: 400, description: "Некорректный запрос"),
            new OA\Response(response: 404, description: "Продукты не найдены")
        ]
    )]
    #[Route('/products/search', name: 'proxy_product_detail', methods: ['GET', 'PUT', 'DELETE'])]
    public function proxyProductDetailSearch(Request $request, string $id): JsonResponse
    {
        return $this->proxyProductService($request, $id);
    }

    /**
     * Проксирует запросы к Product Service.
     *
     * @param Request $request Входящий HTTP-запрос.
     * @param string|null $id Идентификатор продукта (если применимо).
     *
     * @return JsonResponse Ответ от Product Service.
     */

    #[Route('/products/{id?}', name: 'proxy_product', methods: ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'])]
    public function proxyProductService(Request $request, ?string $id = null): JsonResponse
    {
        $path = '/api/products' . ($id ? "/$id" : '');
        $method = $request->getMethod();

        if (!$this->isRouteAllowed('product-service', $path, $method)) {
            return new JsonResponse(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $url = 'http://product-service' . $path;

        try {
            $response = $this->httpClient->request(
                $method,
                $url,
                ['json' => json_decode($request->getContent(), true)]
            );

            return new JsonResponse(
                $method === 'DELETE' ? [] : $response->toArray(false),
                $response->getStatusCode()
            );
        } catch (ClientExceptionInterface $e) {
            $this->logger->error('Client error occurred', ['message' => $e->getMessage(), 'url' => $url]);
            return new JsonResponse(['error' => 'Client error'], Response::HTTP_BAD_REQUEST);
        } catch (ServerExceptionInterface $e) {
            $this->logger->error('Server error occurred', ['message' => $e->getMessage(), 'url' => $url]);
            return new JsonResponse(['error' => 'Server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (RedirectionExceptionInterface $e) {
            $this->logger->error('Redirection error occurred', ['message' => $e->getMessage(), 'url' => $url]);
            return new JsonResponse(['error' => 'Redirection error'], Response::HTTP_BAD_GATEWAY);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Transport error occurred', ['message' => $e->getMessage(), 'url' => $url]);
            return new JsonResponse(['error' => 'Transport error'], Response::HTTP_SERVICE_UNAVAILABLE);
        } catch (DecodingExceptionInterface $e) {
            $this->logger->error('Decoding error occurred', ['message' => $e->getMessage(), 'url' => $url]);
            return new JsonResponse(['error' => 'Decoding error'], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error occurred', ['message' => $e->getMessage(), 'url' => $url]);
            return new JsonResponse(['error' => 'Unexpected error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Проксирует запросы к Order Service.
     *
     * @param Request $request Входящий HTTP-запрос.
     * @param string|null $id Идентификатор заказа (если применимо).
     *
     * @return JsonResponse Ответ от Order Service.
     */
    #[OA\Get(
        path: "/api/orders/{id}",
        summary: "Получить заказ по ID",
        tags: ["Order Service"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "ID заказа",
                in: "path",
                required: false,
                schema: new OA\Schema(type: "string", format: "uuid")
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Успешный ответ"),
            new OA\Response(response: 403, description: "Доступ запрещён"),
            new OA\Response(response: 502, description: "Ошибка сервиса")
        ]
    )]
    #[OA\Put(
        path: "/api/orders/{id}",
        summary: "Обновить данные заказа",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent()
        ),
        tags: ["Order Service"],
        responses: [
            new OA\Response(response: 200, description: "Данные обновлены"),
            new OA\Response(response: 404, description: "Заказ не найден")
        ]
    )]
    #[OA\Delete(
        path: "/api/orders/{id}",
        summary: "Удалить заказ",
        tags: ["Order Service"],
        responses: [
            new OA\Response(response: 204, description: "Заказ удалён"),
            new OA\Response(response: 404, description: "Заказ не найден")
        ]
    )]
    #[OA\Get(
        path: "/api/orders/search",
        summary: "Поиск заказов по ID продукта",
        tags: ["Order Service"],
        parameters: [
            new OA\Parameter(
                name: "productId",
                description: "ID продукта для поиска заказов",
                in: "query",
                required: true,
                schema: new OA\Schema(type: "string", format: "uuid")
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Результаты поиска", content: new OA\JsonContent(type: "array", items: new OA\Items())),
            new OA\Response(response: 400, description: "Некорректный запрос"),
            new OA\Response(response: 404, description: "Заказы не найдены")
        ]
    )]
    #[Route('/orders/{id?}', name: 'proxy_order', methods: ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'])]
    public function proxyOrderService(Request $request, ?string $id = null): JsonResponse
    {
        $path = '/api/orders' . ($id ? "/$id" : '');
        $method = $request->getMethod();

        if (!$this->isRouteAllowed('order-service', $path, $method)) {
            return new JsonResponse(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $url = 'http://order-service' . $path;

        try {
            $response = $this->httpClient->request(
                $method,
                $url,
                ['json' => json_decode($request->getContent(), true)]
            );

            return new JsonResponse(
                $method === 'DELETE' ? [] : $response->toArray(false),
                $response->getStatusCode()
            );
        } catch (ClientExceptionInterface $e) {
            $this->logger->error('Client error occurred', ['message' => $e->getMessage(), 'url' => $url]);
            return new JsonResponse(['error' => 'Client error'], Response::HTTP_BAD_REQUEST);
        } catch (ServerExceptionInterface $e) {
            $this->logger->error('Server error occurred', ['message' => $e->getMessage(), 'url' => $url]);
            return new JsonResponse(['error' => 'Server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (RedirectionExceptionInterface $e) {
            $this->logger->error('Redirection error occurred', ['message' => $e->getMessage(), 'url' => $url]);
            return new JsonResponse(['error' => 'Redirection error'], Response::HTTP_BAD_GATEWAY);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Transport error occurred', ['message' => $e->getMessage(), 'url' => $url]);
            return new JsonResponse(['error' => 'Transport error'], Response::HTTP_SERVICE_UNAVAILABLE);
        } catch (DecodingExceptionInterface $e) {
            $this->logger->error('Decoding error occurred', ['message' => $e->getMessage(), 'url' => $url]);
            return new JsonResponse(['error' => 'Decoding error'], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error occurred', ['message' => $e->getMessage(), 'url' => $url]);
            return new JsonResponse(['error' => 'Unexpected error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Проксирует запрос для получения документации Product Service.
     *
     * @return Response Ответ с документацией или сообщение об ошибке, если сервис недоступен.
     */
    #[Route('/doc-product-service', name: 'proxy_doc_product', methods: ['GET'])]
    public function proxyProductDoc(): Response
    {
        try {
            $response = $this->httpClient->request('GET', 'http://product-service/api/doc');

            return new Response(
                $response->getContent(),
                $response->getStatusCode(),
                $response->getHeaders(false)
            );
        } catch (ClientExceptionInterface | ServerExceptionInterface | RedirectionExceptionInterface | TransportExceptionInterface $e) {
            return new Response('Service unavailable', 502);
        }
    }

    /**
     * Проксирует запрос для получения документации Order Service.
     *
     * @return Response Ответ с документацией или сообщение об ошибке, если сервис недоступен.
     */
    #[Route('/doc-order-service', name: 'proxy_doc_order', methods: ['GET'])]
    public function proxyOrderDoc(): Response
    {
        try {
            $response = $this->httpClient->request('GET', 'http://order-service/api/doc');

            return new Response(
                $response->getContent(),
                $response->getStatusCode(),
                $response->getHeaders(false)
            );
        } catch (ClientExceptionInterface | ServerExceptionInterface | RedirectionExceptionInterface | TransportExceptionInterface $e) {
            return new Response('Service unavailable', 502);
        }
    }
}
