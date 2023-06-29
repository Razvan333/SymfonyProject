<?php

namespace App\Service;

use App\Validator\Data;
use Predis\Client;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
class CustomerControllerService
{
    public function validateData(array $data, ValidatorInterface $validator, SerializerInterface $serializer): JsonResponse
    {
        $violations = $validator->validate($data, new Data());
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getMessage();
            }
            $errors = $serializer->serialize(['errors' =>  $errors], 'json');

            return new JsonResponse($errors, Response::HTTP_BAD_REQUEST, ['Content-Type' => 'application/json'], true);
        }
        $success = $serializer->serialize(['success' => 'validation_success'], 'json');

        return new JsonResponse($success, Response::HTTP_OK, ['Content-Type' => 'application/json'], true);
    }

    public function removeCache(Client $cache, string $allCustomersCache, string $customerCache = ''): void
    {
        $cacheKeys = $cache->keys('*');
        foreach ($cacheKeys as $cacheKey) {
            if (str_starts_with($cacheKey, $allCustomersCache)) {
                $cache->del($cacheKey);
            }
        }

        if (!empty($customerCache)) {
            $cache->del($customerCache);
        }
    }

    public function formatCustomerName(string $customerName): array
    {
        $customerFullName = [
            'firstName' => '',
            'lastName' => ''
        ];

        if (!empty($customerName)) {
            $fullName = explode(' ', $customerName);
            $customerFullName['firstName'] = array_pop($fullName);
            $customerFullName['lastName'] = implode(' ', $fullName);
        }

        return $customerFullName;
    }
}