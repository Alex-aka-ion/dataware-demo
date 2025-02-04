<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
class ApiDocController
{
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    #[Route('/api/docs.json', name: 'api_docs', methods: ['GET'])]
    public function aggregateDocs(): JsonResponse
    {
        $services = [
            'product' => 'http://product-service/api/doc.json',
            'order'   => 'http://order-service/api/doc.json',
        ];

        $combinedDocs = [
            'openapi' => '3.0.0',
            'info'    => [
                'title'       => 'Aggregated API Documentation',
                'version'     => '1.0.0',
                'description' => 'Документация, объединённая для всех сервисов',
            ],
            'paths'    => [],
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

            } catch (\Exception $e) {
                // Логируем ошибки, если сервис недоступен
                error_log("Не удалось загрузить документацию от {$name}: " . $e->getMessage());
            }
        }

        return new JsonResponse($combinedDocs);
    }
}
