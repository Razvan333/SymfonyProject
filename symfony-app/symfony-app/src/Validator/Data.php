<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 *
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Data extends Constraint
{
    public $fields = [];

    public function __construct(mixed $options = null, array $groups = null, mixed $payload = null)
    {
        parent::__construct($options, $groups, $payload);

        $this->fields = [
            'id' => '/^[0-9]+$/',
            'customer_address' => '/^[a-zA-Z0-9.\săâțșî]+$/',
            'customer_name' => '/^[a-zA-Z\săâțșî]+$/',
        ];
    }

    public function getMessage($fieldName): string
    {
        return match ($fieldName) {
            'customer_id' => 'Invalid customer ID: {{ value }}',
            'customer_address' => 'Invalid customer address: {{ value }}',
            'customer_name' => 'Invalid customer name: {{ value }}',
            default => 'Invalid field: {{ value }}',
        };
    }
}
