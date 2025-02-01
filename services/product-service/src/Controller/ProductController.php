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
use Symfony\Component\Validator\Validator\ValidatorInterface;

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

    // Получить список всех продуктов
    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $products = $this->productRepository->findAll();
        return $this->json($products);
    }

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

    // Получить продукт по ID
    #[Route('/{id}', requirements: ['id' => '[0-9a-fA-F-]{36}'], methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        $product = $this->productRepository->find($id);

        if (!$product) {
            return $this->json(['error' => 'Продукт не найден'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($product);
    }

    // Создать новый продукт
    #[Route('', methods: ['POST'])]
    public function create(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['error' => 'Некорректный формат JSON'], Response::HTTP_BAD_REQUEST);
        }

        // Использование DTO для валидации данных
        $productRequest = new ProductRequest(
            $data['name'] ?? '',
            (float) ($data['price'] ?? 0),
            $data['categories'] ?? [],
            $data['description'] ?? ''
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

    // Обновить продукт
    #[Route('/{id}', methods: ['PUT'])]
    public function update(string $id, Request $request, ValidatorInterface $validator): JsonResponse
    {
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
            isset($data['price']) ? (float) $data['price'] : null,
            $data['categories'] ?? null,
            $data['description'] ?? null
        );

        $errors = $validator->validate($productRequest, null, ['update']);
        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
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

    // Удалить продукт
    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        $product = $this->productRepository->find($id);

        if (!$product) {
            return $this->json(['error' => 'Продукт не найден'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($product);
        $this->entityManager->flush();

        return $this->json(['message' => 'Product deleted'], Response::HTTP_NO_CONTENT);
    }
}