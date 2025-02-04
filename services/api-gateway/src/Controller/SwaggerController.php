<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SwaggerController extends AbstractController
{
    #[Route('/swagger', name: 'swagger_ui')]
    public function index(): Response
    {
        return $this->render('swagger/index.html.twig', [
            'swagger_json_url' => '/api/docs.json', // Укажите сюда ваш OpenAPI JSON
        ]);
    }
}
