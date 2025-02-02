<?php

namespace App\Tests\DTO;

use App\DTO\SearchByProductIdRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

class SearchByProductIdRequestTest extends TestCase
{
    private $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testValidSearchByProductIdRequest(): void
    {
        $request = new SearchByProductIdRequest(
            productId: '0194bd9a-d8b5-7de7-873d-db4907a13836'
        );

        $errors = $this->validator->validate($request);
        $this->assertCount(0, $errors, 'Ожидается отсутствие ошибок для корректных данных.');
    }

    public function testMissingProductId(): void
    {
        $request = new SearchByProductIdRequest(
            productId: '' // Пустой productId
        );

        $errors = $this->validator->validate($request);
        $this->assertGreaterThan(0, count($errors), 'Ожидаются ошибки из-за отсутствия productId.');
    }

    public function testInvalidProductIdType(): void
    {
        $request = new SearchByProductIdRequest(
            productId: 12345 // Неверный тип данных (integer вместо строки)
        );

        $errors = $this->validator->validate($request);
        $this->assertGreaterThan(0, count($errors), 'Ожидаются ошибки из-за некорректного типа данных для productId.');
    }

    public function testInvalidProductIdFormat(): void
    {
        $request = new SearchByProductIdRequest(
            productId: 'invalid-uuid' // Неверный формат UUID
        );

        $errors = $this->validator->validate($request);
        $this->assertGreaterThan(0, count($errors), 'Ожидаются ошибки из-за неверного формата UUID для productId.');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->validator = null;
    }
}