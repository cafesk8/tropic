<?php

declare(strict_types=1);

namespace App\Component\Transfer\Pohoda\Customer;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class PohodaAddressConstraint extends Constraint
{
    /**
     * @return string
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

    /**
     * @return string
     */
    public function validatedBy()
    {
        return \get_class($this) . 'Validator';
    }
}
