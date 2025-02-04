<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Контроллер для отображения Swagger UI.
 *
 * Этот контроллер отвечает за рендеринг страницы Swagger UI, которая используется для визуализации
 * и взаимодействия с API-документацией, основанной на спецификации OpenAPI.
 *
 * Основные функции:
 * - Отображение интерфейса Swagger UI.
 * - Интеграция с агрегированной OpenAPI-документацией, доступной по адресу `/api/docs.json`.
 */
class SwaggerController extends AbstractController
{
    /**
     * Отображает Swagger UI.
     *
     * Этот метод рендерит шаблон `swagger/index.html.twig`, который подключает Swagger UI и загружает
     * документацию OpenAPI из указанного JSON-файла (`/api/docs.json`).
     *
     * @return Response HTTP-ответ с отрендеренной страницей Swagger UI.
     */
    #[Route('/swagger', name: 'swagger_ui')]
    public function index(): Response
    {
        return $this->render('swagger/index.html.twig', [
            'swagger_json_url' => '/api/docs.json', // мой общий OpenAPI JSON
        ]);
    }
}
