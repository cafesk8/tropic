<?php

declare(strict_types=1);

namespace App\Component\Transfer\Pohoda\Order\Status;

use App\Component\Transfer\Pohoda\Exception\PohodaInvalidDataException;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PohodaOrderStatusDataValidator
{
    private ValidatorInterface $validator;

    /**
     * @param \Symfony\Component\Validator\Validator\ValidatorInterface $validator
     */
    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param array $pohodaOrderStatusData
     * @throws \App\Component\Transfer\Pohoda\Exception\PohodaInvalidDataException
     */
    public function validate(array $pohodaOrderStatusData): void
    {
        $violations = $this->validator->validate($pohodaOrderStatusData, new Collection([
            'allowExtraFields' => true,
            'fields' => [
                PohodaOrderStatus::COL_POHODA_ORDER_ID => [
                    new Type(['type' => 'numeric']),
                    new NotBlank(),
                    new GreaterThan(['value' => 0]),
                ],
                PohodaOrderStatus::COL_POHODA_STATUS_ID => [
                    new Type(['type' => 'numeric']),
                    new NotBlank(),
                    new GreaterThan(['value' => 0]),
                ],
                PohodaOrderStatus::COL_POHODA_STATUS_NAME => [
                    new Type(['type' => 'string']),
                ],
            ],
        ]));

        if (count($violations) > 0) {
            throw new PohodaInvalidDataException($violations);
        }
    }
}
