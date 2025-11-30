<?php

namespace App\Validator;

use App\Entity\Roster;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class RosterPointsValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof Roster) {
            return;
        }

        if ($value->getTotalCost() > $value->getPointsLimit()) {
            $this->context->buildViolation('Roster exceeds points limit')
                ->addViolation();
        }
    }
}
