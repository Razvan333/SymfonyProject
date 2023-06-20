<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class DataValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        /* @var App\Validator\Data $constraint */

        if (null === $value || '' === $value) {
            return;
        }

        if (!preg_match('/^[0-9]+$/', $value[0])) {
            $this->context->buildViolation($constraint->numberMessage)
                ->setParameter('{{ value }}', $value[0])
                ->addViolation();
        }

        if (!preg_match('/^[a-zA-Z0-9.\săâțșî]+$/', $value[1])) {
            $this->context->buildViolation($constraint->addressMessage)
                ->setParameter('{{ value }}', $value[1])
                ->addViolation();
        }

        if (!empty($value[2])) {
            if (!preg_match('/^[a-zA-Z\săâțșî]+$/', $value[2])) {
                $this->context->buildViolation($constraint->fullNameMessage)
                    ->setParameter('{{ value }}', $value[2])
                    ->addViolation();
            }
        }
    }
}
