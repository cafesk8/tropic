<?php

declare(strict_types=1);

namespace App\Component\Transfer\Pohoda\Order;

use Symfony\Component\Validator\Constraint;

class PohodaOrderItemsConstraint extends Constraint
{
    /**
     * @return string
     */
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }

    /**
     * @return string
     */
    public function validatedBy(): string
    {
        return \get_class($this) . 'Validator';
    }
}
