<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class RaceResultAllValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof RaceResultAll) {
            throw new UnexpectedTypeException($constraint, RaceResultAll::class);
        }

        if (null === $value) {
            return;
        }

        if (!\is_array($value) && !$value instanceof \Traversable) {
            throw new UnexpectedValueException($value, 'iterable');
        }

        $context = $this->context;

        $validator = $context->getValidator()->inContext($context);

        foreach ($value as $key => $element) {
            $row = $key + $constraint->headerRows + 1;
            $validator->atPath('['.$row.']')->validate($element, $constraint->constraints);
        }
    }
}
