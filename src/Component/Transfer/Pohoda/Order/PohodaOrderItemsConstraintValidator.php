<?php

declare(strict_types=1);

namespace App\Component\Transfer\Pohoda\Order;

use App\Component\Transfer\Pohoda\Exception\PohodaInvalidDataException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PohodaOrderItemsConstraintValidator extends ConstraintValidator
{
    /**
     * @var \Symfony\Component\Validator\Validator\ValidatorInterface
     */
    private ValidatorInterface $validator;

    /**
     * @param \Symfony\Component\Validator\Validator\ValidatorInterface $validator
     */
    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param \App\Component\Transfer\Pohoda\Order\PohodaOrderItem[] $pohodaOrderItems
     * @param \Symfony\Component\Validator\Constraint $constraint
     */
    public function validate($pohodaOrderItems, Constraint $constraint): void
    {
        if (empty($pohodaOrderItems)) {
            return;
        }

        foreach ($pohodaOrderItems as $pohodaOrderItem) {
            $violations = $this->validator->validate($pohodaOrderItem->getAsArray(), new Collection([
                'allowExtraFields' => true,
                'fields' => [
                    'name' => [
                        new Type(['type' => 'string']),
                        new Length(['max' => 90]),
                    ],
                    'catnum' => [
                        new Type(['type' => 'string']),
                    ],
                    'quantity' => [
                        new Type(['type' => 'integer']),
                    ],
                    'unit' => [
                        new Type(['type' => 'string']),
                    ],
                    'pohodaStockName' => [
                        new Type(['type' => 'string']),
                        new Length(['max' => 32]),
                    ],
                ],
            ]));

            if (count($violations) > 0) {
                throw new PohodaInvalidDataException($violations);
            }
        }
    }
}
