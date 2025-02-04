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

/**
 * Контроллер API-шлюза для маршрутизации запросов к микросервисам продуктов и заказов.
 *
 * Этот контроллер обрабатывает HTTP-запросы и проксирует их в соответствующие сервисы:
 * - **Product Service**: операции с продуктами (создание, получение, обновление, удаление).
 * - **Order Service**: операции с заказами (создание, получение, обновление, удаление).
 *
 * Контроллер также ограничивает доступ к определённым маршрутам и методам в соответствии с настройками разрешений.
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
     * @param LoggerInterface $logger Объект для логирования.
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

        $url = 'http://product-service' . $path . ($request->getQueryString() ? '?' . $request->getQueryString() : '');

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
    #[Route('/orders/{id?}', name: 'proxy_order', methods: ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'])]
    public function proxyOrderService(Request $request, ?string $id = null): JsonResponse
    {
        $path = '/api/orders' . ($id ? "/$id" : '');
        $method = $request->getMethod();

        if (!$this->isRouteAllowed('order-service', $path, $method)) {
            return new JsonResponse(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $url = 'http://order-service' . $path . ($request->getQueryString() ? '?' . $request->getQueryString() : '');

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
}
