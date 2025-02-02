<?php

namespace App\Controller;

use App\DTO\ProductRequest;
use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

#[AsController]
#[Route('/api/products', name: 'product_')]
final class ProductController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProductRepository      $productRepository
    )
    {
    }

    #[OA\Get(
        path: '/api/products',
        summary: 'Получить список всех продуктов',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Список продуктов',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Product'))
            )
        ]
    )]
    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $products = $this->productRepository->findAll();
        return $this->json($products);
    }


    #[OA\Get(
        path: '/api/products/search',
        summary: 'Поиск продуктов по имени',
        parameters: [
            new OA\Parameter(
                name: 'name',
                description: 'Имя продукта',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(response: 200, description: 'Результаты поиска'),
            new OA\Response(response: 400, description: 'Ошибка запроса')
        ]
    )]
    #[Route('/search', methods: ['GET'])]
    public function searchByName(Request $request, ProductRepository $productRepository): JsonResponse
    {
        $name = $request->query->get('name');

        if (!$name) {
            return new JsonResponse(['error' => 'Параметр "name" обязателен'], Response::HTTP_BAD_REQUEST);
        }

        $products = $productRepository->findByName($name);

        return $this->json($products, Response::HTTP_OK);
    }


    #[OA\Get(
        path: '/api/products/{id}',
        summary: 'Получить продукт по ID',
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            )
        ],
        responses: [
            new OA\Response(response: 200, description: 'Информация о продукте'),
            new OA\Response(response: 404, description: 'Продукт не найден')
        ]
    )]
    #[Route('/{id}', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        // Проверка корректности UUID
        if (!Uuid::isValid($id)) {
            return $this->json(['error' => 'Некорректный формат UUID'], Response::HTTP_BAD_REQUEST);
        }

        $product = $this->productRepository->find($id);

        if (!$product) {
            return $this->json(['error' => 'Продукт не найден'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($product);
    }

    #[OA\Post(
        path: '/api/products',
        summary: 'Создать новый продукт',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ProductRequest')
        ),
        responses: [
            new OA\Response(response: 201, description: 'Продукт создан'),
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

        // Использование DTO для валидации данных
        $productRequest = new ProductRequest(
            $data['name'] ?? null,
            (float) ($data['price'] ?? null),
            $data['categories'] ?? [],
            $data['description'] ?? null
        );

        // Валидация DTO
        $errors = $validator->validate($productRequest);
        if (count($errors) > 0) {
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        // Создание объекта Product на основе данных DTO
        $product = new Product();
        $product->setName(strip_tags($productRequest->name));
        $product->setDescription(strip_tags($productRequest->description));
        $product->setPrice($productRequest->price);
        $product->setCategories($productRequest->categories);

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $this->json($product, Response::HTTP_CREATED);
    }

    #[OA\Put(
        path: '/api/products/{id}',
        summary: 'Обновить продукт',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ProductRequest')
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
            new OA\Response(response: 200, description: 'Продукт обновлён'),
            new OA\Response(response: 404, description: 'Продукт не найден')
        ]
    )]
    #[Route('/{id}', methods: ['PUT'])]
    public function update(string $id, Request $request, ValidatorInterface $validator): JsonResponse
    {
        // Проверка корректности UUID
        if (!Uuid::isValid($id)) {
            return $this->json(['error' => 'Некорректный формат UUID'], Response::HTTP_BAD_REQUEST);
        }

        $product = $this->productRepository->find($id);

        if (!$product) {
            return $this->json(['error' => 'Продукт не найден'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['error' => 'Некорректный формат JSON'], Response::HTTP_BAD_REQUEST);
        }

        // Использование DTO для валидации
        $productRequest = new ProductRequest(
            $data['name'] ?? null,
            $data['price'] ?? null,
            $data['categories'] ?? [],
            $data['description'] ?? null
        );

        $errors = $validator->validate($productRequest);
        if (count($errors) > 0) {
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['name'])) {
            $product->setName(strip_tags($productRequest->name));
        }
        if (isset($data['description'])) {
            $product->setDescription(strip_tags($productRequest->description));
        }
        if (isset($data['price'])) {
            $product->setPrice((float)$data['price']);
        }
        if (isset($data['categories'])) {
            $product->setCategories($data['categories']);
        }

        $errors = $validator->validate($product);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        return $this->json($product);
    }

    #[OA\Delete(
        path: '/api/products/{id}',
        summary: 'Удалить продукт',
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            )
        ],
        responses: [
            new OA\Response(response: 204, description: 'Продукт удалён'),
            new OA\Response(response: 404, description: 'Продукт не найден')
        ]
    )]
    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        // Проверка корректности UUID
        if (!Uuid::isValid($id)) {
            return $this->json(['error' => 'Некорректный формат UUID'], Response::HTTP_BAD_REQUEST);
        }

        $product = $this->productRepository->find($id);

        if (!$product) {
            return $this->json(['error' => 'Продукт не найден'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($product);
        $this->entityManager->flush();

        return $this->json(['message' => 'Продукт удалён'], Response::HTTP_NO_CONTENT);
    }
}