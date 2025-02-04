<?php

namespace App\Controller;

use App\DTO\ProductRequest;
use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

/**
 * Контроллер для управления продуктами.
 *
 * Этот контроллер предоставляет REST API для управления продуктами,
 * включая создание, получение, обновление и удаление записей о продуктах.
 */
#[AsController]
#[Route('/api/products', name: 'product_')]
final class ProductController extends AbstractController
{
    /**
     * @param EntityManagerInterface $entityManager Менеджер сущностей Doctrine для работы с БД.
     * @param ProductRepository $productRepository Репозиторий для работы с сущностью Product.
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProductRepository      $productRepository
    )
    {
    }

    /**
     * Получение списка всех продуктов.
     *
     * @return JsonResponse Список продуктов в формате JSON.
     */
    #[OA\Get(
        path: '/api/products',
        summary: 'Получить список всех продуктов',
        tags: ['Products'],
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

    /**
     * Поиск продуктов по имени.
     *
     * @param Request $request HTTP-запрос с параметром 'name'.
     * @param ProductRepository $productRepository Репозиторий для поиска продуктов.
     * @return JsonResponse Результаты поиска или сообщение об ошибке.
     */
    #[OA\Get(
        path: '/api/products/search',
        summary: 'Поиск продуктов по имени',
        tags: ['Products'],
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

    /**
     * Получение информации о продукте по его ID.
     *
     * @param string $id Идентификатор продукта (UUID).
     * @return JsonResponse Информация о продукте или сообщение об ошибке.
     */
    #[OA\Get(
        path: '/api/products/{id}',
        operationId: 'getProductById',
        description: 'Возвращает подробную информацию о продукте по его уникальному идентификатору (UUID).',
        summary: 'Получить продукт по ID',
        tags: ['Products'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'UUID идентификатор продукта',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid'),
                example: '123e4567-e89b-12d3-a456-426614174000'
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Информация о продукте',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '123e4567-e89b-12d3-a456-426614174000'),
                        new OA\Property(property: 'name', type: 'string', example: 'Ноутбук ASUS'),
                        new OA\Property(property: 'description', type: 'string', example: 'Мощный игровой ноутбук с 16 ГБ оперативной памяти.'),
                        new OA\Property(property: 'price', type: 'number', format: 'float', example: 1499.99),
                        new OA\Property(property: 'categories', type: 'array', items: new OA\Items(type: 'string'), example: ['Электроника', 'Компьютеры'])
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Некорректный формат UUID',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Некорректный формат UUID')
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Продукт не найден',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Продукт не найден')
                    ],
                    type: 'object'
                )
            )
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

    /**
     * Создание нового продукта.
     *
     * @param Request $request HTTP-запрос с данными продукта.
     * @param ValidatorInterface $validator Валидатор для проверки данных.
     * @return JsonResponse Созданный продукт или сообщение об ошибке.
     */
    #[OA\Post(
        path: '/api/products',
        operationId: 'createProduct',
        description: 'Позволяет создать новый продукт, передав необходимые данные в формате JSON.',
        summary: 'Создать новый продукт',
        requestBody: new OA\RequestBody(
            description: 'Данные для создания продукта',
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'price', 'categories'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Ноутбук ASUS'),
                    new OA\Property(property: 'price', type: 'number', format: 'float', example: 1499.99),
                    new OA\Property(property: 'categories', type: 'array', items: new OA\Items(type: 'string'), example: ['Электроника', 'Компьютеры']),
                    new OA\Property(property: 'description', type: 'string', example: 'Мощный игровой ноутбук с 16 ГБ оперативной памяти и SSD на 512 ГБ.')
                ],
                type: 'object'
            )
        ),
        tags: ['Products'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Продукт успешно создан',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '123e4567-e89b-12d3-a456-426614174000'),
                        new OA\Property(property: 'name', type: 'string', example: 'Ноутбук ASUS'),
                        new OA\Property(property: 'price', type: 'number', format: 'float', example: 1499.99),
                        new OA\Property(property: 'categories', type: 'array', items: new OA\Items(type: 'string'), example: ['Электроника', 'Компьютеры']),
                        new OA\Property(property: 'description', type: 'string', example: 'Мощный игровой ноутбук.')
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Ошибка валидации данных',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Некорректный формат JSON'),
                        new OA\Property(property: 'errors', type: 'array', items: new OA\Items(type: 'string'), example: ['Название продукта не может быть пустым', 'Цена должна быть положительным числом'])
                    ],
                    type: 'object'
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

        $categories = array_map(fn($category) => strip_tags($category), $productRequest->categories);

        // Создание объекта Product на основе данных DTO
        $product = new Product();
        $product->setName(strip_tags($productRequest->name));
        $product->setDescription(strip_tags($productRequest->description));
        $product->setPrice($productRequest->price);
        $product->setCategories($categories);

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $this->json($product, Response::HTTP_CREATED);
    }

    /**
     * Обновление данных продукта.
     *
     * @param string $id Идентификатор продукта (UUID).
     * @param Request $request HTTP-запрос с обновленными данными.
     * @param ValidatorInterface $validator Валидатор для проверки данных.
     * @return JsonResponse Обновленный продукт или сообщение об ошибке.
     */
    #[OA\Put(
        path: '/api/products/{id}',
        operationId: 'updateProduct',
        description: 'Позволяет обновить данные существующего продукта по его UUID.',
        summary: 'Обновить продукт',
        requestBody: new OA\RequestBody(
            description: 'Данные для обновления продукта',
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Обновленный ноутбук ASUS'),
                    new OA\Property(property: 'price', type: 'number', format: 'float', example: 1599.99),
                    new OA\Property(property: 'categories', type: 'array', items: new OA\Items(type: 'string'), example: ['Электроника', 'Компьютеры']),
                    new OA\Property(property: 'description', type: 'string', example: 'Обновленное описание продукта.')
                ],
                type: 'object'
            )
        ),
        tags: ['Products'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'UUID идентификатор продукта',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid'),
                example: '123e4567-e89b-12d3-a456-426614174000'
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Продукт успешно обновлен',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '123e4567-e89b-12d3-a456-426614174000'),
                        new OA\Property(property: 'name', type: 'string', example: 'Обновленный ноутбук ASUS'),
                        new OA\Property(property: 'price', type: 'number', format: 'float', example: 1599.99),
                        new OA\Property(property: 'categories', type: 'array', items: new OA\Items(type: 'string'), example: ['Электроника', 'Компьютеры']),
                        new OA\Property(property: 'description', type: 'string', example: 'Обновленное описание продукта.')
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Некорректный запрос или ошибка валидации данных',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Некорректный формат JSON'),
                        new OA\Property(property: 'errors', type: 'array', items: new OA\Items(type: 'string'), example: ['Название продукта не может быть пустым', 'Цена должна быть положительным числом'])
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Продукт не найден',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Продукт не найден')
                    ],
                    type: 'object'
                )
            )
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
            $product->setPrice((float)$productRequest->price);
        }
        if (isset($data['categories'])) {
            $categories = array_map(fn($category) => strip_tags($category), $productRequest->categories);
            $product->setCategories($categories);
        }

        $errors = $validator->validate($product);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        return $this->json($product);
    }

    /**
     * Удаление продукта по его ID.
     *
     * @param string $id Идентификатор продукта (UUID).
     * @return JsonResponse Сообщение об успешном удалении или ошибка.
     */
    #[OA\Delete(
        path: '/api/products/{id}',
        summary: 'Удалить продукт',
        tags: ['Products'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'UUID идентификатор продукта',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid'),
                example: '123e4567-e89b-12d3-a456-426614174000'
            )
        ],
        responses: [
            new OA\Response(response: 204, description: 'Продукт удалён'),
            new OA\Response(response: 400, description: 'Некорректный запрос'),
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