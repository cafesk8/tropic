<?php

declare(strict_types=1);

namespace App\Component\Transfer\Pohoda\Product;

use App\Component\Transfer\Pohoda\Exception\PohodaInvalidDataException;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PohodaProductDataValidator
{
    /**
     * @var \Symfony\Component\Validator\Validator\ValidatorInterface
     */
    private $validator;

    /**
     * @param \Symfony\Component\Validator\Validator\ValidatorInterface $validator
     */
    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param array $pohodaProductData
     */
    public function validate(array $pohodaProductData): void
    {
        $violations = $this->validator->validate($pohodaProductData, new Collection([
            'allowExtraFields' => true,
            'fields' => [
                PohodaProduct::COL_POHODA_ID => [
                    new Type(['type' => 'string']),
                    new NotBlank(),
                ],
                PohodaProduct::COL_CATNUM => [
                    new Type(['type' => 'string']),
                    new NotBlank(),
                ],
                PohodaProduct::COL_NAME => [
                    new Type(['type' => 'string']),
                    new NotBlank(),
                ],
            ],
        ]));

        if (count($violations) > 0) {
            throw new PohodaInvalidDataException($violations);
        }
    }
}
