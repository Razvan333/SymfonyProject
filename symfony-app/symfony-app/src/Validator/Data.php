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
    /*
     * Any public properties become valid options for the annotation.
     * Then, use these in your validator class.
     */
    public $numberMessage = 'Customer id is not in a valid format: {{ value }}';
    public $fullNameMessage = 'Customer name is not in a valid format: {{ value }}';
    public $addressMessage = 'Customer address is not in a valid format: {{ value }}';
}
