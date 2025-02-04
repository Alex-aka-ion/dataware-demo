<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Контроллер для агрегации OpenAPI-документации от различных микросервисов.
 *
 * Этот контроллер собирает документацию из нескольких сервисов (например, Product Service и Order Service)
 * и объединяет их в один JSON-документ, соответствующий спецификации OpenAPI 3.0.0.
 *
 * Основные функции:
 * - Отправка HTTP-запросов к эндпоинтам микросервисов для получения их документации.
 * - Объединение путей (paths) и схем данных (schemas) в одну спецификацию.
 * - Обработка ошибок, если какой-либо сервис недоступен.
 *
 * @package App\Controller
 */
#[AsController]
readonly class ApiDocController
{

    /**
     * Конструктор контроллера.
     *
     * @param HttpClientInterface $httpClient Клиент для отправки HTTP-запросов к другим сервисам.
     * @param LoggerInterface $logger Объект для логирования.
     */
    public function __construct(private HttpClientInterface $httpClient,
                                private LoggerInterface     $logger)
    {
    }

    /**
     * Агрегация документации из всех микросервисов.
     *
     * @return JsonResponse JSON-документация в формате OpenAPI 3.0.0, объединённая для всех сервисов.
     */
    #[Route('/api/docs.json', name: 'api_docs', methods: ['GET'])]
    public function aggregateDocs(): JsonResponse
    {
        $services = [
            'product' => 'http://product-service/api/doc.json',
            'order' => 'http://order-service/api/doc.json',
        ];

        $combinedDocs = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'Aggregated API Documentation',
                'version' => '1.0.0',
                'description' => 'Документация, объединённая для всех сервисов',
            ],
            'paths' => [],
            'components' => [
                'schemas' => [],
            ],
        ];

        foreach ($services as $name => $url) {
            try {
                $response = $this->httpClient->request('GET', $url);
                $serviceDocs = $response->toArray();

                // Объединяем пути
                $combinedDocs['paths'] = array_merge($combinedDocs['paths'], $serviceDocs['paths'] ?? []);

                // Объединяем схемы
                if (isset($serviceDocs['components']['schemas'])) {
                    $combinedDocs['components']['schemas'] = array_merge(
                        $combinedDocs['components']['schemas'],
                        $serviceDocs['components']['schemas']
                    );
                }
            } catch (\Exception|TransportExceptionInterface|ClientExceptionInterface|DecodingExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface $e) {
                $this->logger->error("Не удалось загрузить документацию от {$name}: " . $e->getMessage());
            }
        }

        return new JsonResponse($combinedDocs);
    }
}
