<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsController]
#[Route('/api')]
readonly class GatewayController
{
    private array $allowedRoutes;
    public function __construct(
        private HttpClientInterface $httpClient
    )
    {
        $this->allowedRoutes = [
            'product-service' => [
                '/api/products' => ['GET', 'POST'],               // Разрешаем получить список продуктов и создать новый
                '/api/products/{id}' => ['GET', 'PUT', 'DELETE'], // Разрешаем получить, обновить и удалить продукт
                '/api/products/search' => ['GET'],                // Разрешаем только поиск продуктов
            ],
            'order-service' => [
                '/api/orders' => ['GET', 'POST'],               // Разрешаем получить список заказов и создать новый
                '/api/orders/{id}' => ['GET', 'PUT', 'DELETE'], // Разрешаем получить, обновить и удалить заказ
                '/api/orders/search' => ['GET'],                // Разрешаем поиск заказов по ID продукта
            ],
        ];
    }

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
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Service unavailable'], Response::HTTP_BAD_GATEWAY);
        }
    }

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
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Service unavailable'], Response::HTTP_BAD_GATEWAY);
        }
    }

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
        } catch (\Exception $e) {
            return new Response('Service unavailable', 502);
        }
    }

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
        } catch (\Exception $e) {
            return new Response('Service unavailable', 502);
        }
    }
}
