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

        if (!preg_match('/^[0-9]+$/', $value['id'])) {
            $this->context->buildViolation($constraint->numberMessage)
                ->setParameter('{{ value }}', $value['id'])
                ->addViolation();
        }

        if (!empty($value['customer_address'])) {
            if (!preg_match('/^[a-zA-Z0-9.\săâțșî]+$/', $value['customer_address'])) {
                $this->context->buildViolation($constraint->addressMessage)
                    ->setParameter('{{ value }}', $value['customer_address'])
                    ->addViolation();
            }
        }

        if (!empty($value['customer_name'])) {
            if (!preg_match('/^[a-zA-Z\săâțșî]+$/', $value['customer_name'])) {
                $this->context->buildViolation($constraint->fullNameMessage)
                    ->setParameter('{{ value }}', $value['customer_name'])
                    ->addViolation();
            }
        }
    }
}
