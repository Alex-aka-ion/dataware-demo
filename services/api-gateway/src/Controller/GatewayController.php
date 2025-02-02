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
    public function __construct(
        private HttpClientInterface $httpClient
    )
    {
    }

    #[Route('/products/{id?}', name: 'proxy_product', methods: ['GET', 'POST', 'PUT', 'DELETE'])]
    public function proxyProductService(Request $request, ?string $id = null): JsonResponse
    {
        $url = 'http://product-service/api/products' . ($id ? "/$id" : '');

        try {
            $response = $this->httpClient->request(
                $request->getMethod(),
                $url,
                ['json' => json_decode($request->getContent(), true)]
            );

            return new JsonResponse(
                $response->toArray(),
                $response->getStatusCode()
            );
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Service unavailable'], 502);
        }
    }

    #[Route('/orders/{id?}', name: 'proxy_order', methods: ['GET', 'POST', 'PUT', 'DELETE'])]
    public function proxyOrderService(Request $request, ?string $id = null): JsonResponse
    {
        $url = 'http://order-service/api/orders' . ($id ? "/$id" : '');

        try {
            $response = $this->httpClient->request(
                $request->getMethod(),
                $url,
                ['json' => json_decode($request->getContent(), true)]
            );

            return new JsonResponse(
                $response->toArray(),
                $response->getStatusCode()
            );
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Service unavailable'], 502);
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
