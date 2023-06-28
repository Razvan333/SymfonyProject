<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class DataValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        /* @var App\Validator\Data $constraint */

        if (!is_array($value)) {
            return;
        }

        foreach ($constraint->fields as $fieldName => $fieldRegex) {
            if (array_key_exists($fieldName, $value)) {
                if (!preg_match($fieldRegex, $value[$fieldName])) {
                    $this->context->buildViolation($constraint->getMessage($fieldName))
                        ->setParameter('{{ value }}', $value[$fieldName])
                        ->addViolation();
                }
            }
        }
    }
}
