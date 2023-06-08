<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class CsvDataValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        /* @var App\Validator\CsvData $constraint */

        if (null === $value || '' === $value) {
            return;
        }

        if (!preg_match('/^[0-9]+$/', $value[0])) {
            $this->context->buildViolation($constraint->numberMessage)
                ->setParameter('{{ value }}', $value[0])
                ->addViolation();
        }

        if (!preg_match('/^[a-zA-Z0-9.\s]+$/', $value[1])) {
            $this->context->buildViolation($constraint->addressMessage)
                ->setParameter('{{ value }}', $value[1])
                ->addViolation();
        }
    }
}