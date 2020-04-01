<?php

declare(strict_types=1);

namespace App\Component\Transfer\Pohoda\Category;

use App\Component\Transfer\Pohoda\Exception\PohodaInvalidDataException;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PohodaCategoryDataValidator
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
     * @param array $pohodaCategoryData
     */
    public function validate(array $pohodaCategoryData): void
    {
        $violations = $this->validator->validate($pohodaCategoryData, new Collection([
            'allowExtraFields' => true,
            'fields' => [
                PohodaCategory::COL_POHODA_ID => [
                    new Type(['type' => 'string']),
                    new NotBlank(),
                ],
                PohodaCategory::COL_NAME => [
                    new Type(['type' => 'string']),
                    new NotBlank(),
                ],
                PohodaCategory::COL_PARENT_ID => [
                    new Type(['type' => 'string']),
                    new NotBlank(),
                ],
                PohodaCategory::COL_POSITION => [
                    new Type(['type' => 'string']),
                    new NotBlank(),
                ],
                PohodaCategory::COL_NOT_LISTABLE => [
                    new Type(['type' => 'string']),
                    new NotBlank(),
                ],
                PohodaCategory::COL_LEVEL => [
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
