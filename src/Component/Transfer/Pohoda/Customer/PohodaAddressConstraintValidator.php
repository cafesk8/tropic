<?php

declare(strict_types=1);

namespace App\Component\Transfer\Pohoda\Customer;

use App\Component\Transfer\Pohoda\Exception\PohodaInvalidDataException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PohodaAddressConstraintValidator extends ConstraintValidator
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
     * @param array $pohodaAddress
     * @param \Symfony\Component\Validator\Constraint $constraint
     */
    public function validate($pohodaAddress, Constraint $constraint): void
    {
        $violations = $this->validator->validate($pohodaAddress, new Collection([
            'allowExtraFields' => true,
            'fields' => [
                'company' => [
                    new Type(['type' => 'string']),
                    new Length(['min' => 0, 'max' => 255]),
                ],
                'name' => [
                    new Type(['type' => 'string']),
                    new Length(['min' => 0, 'max' => 64]),
                ],
                'city' => [
                    new Type(['type' => 'string']),
                    new Length(['min' => 0, 'max' => 45]),
                ],
                'street' => [
                    new Type(['type' => 'string']),
                    new Length(['min' => 0, 'max' => 64]),
                ],
                'zip' => [
                    new Type(['type' => 'string']),
                    new Length(['min' => 0, 'max' => 15]),
                ],
                'ico' => [
                    new Type(['type' => 'string']),
                    new Length(['min' => 0, 'max' => 15]),
                ],
                'dic' => [
                    new Type(['type' => 'string']),
                    new Length(['min' => 0, 'max' => 18]),
                ],
                'phone' => [
                    new Type(['type' => 'string']),
                    new Length(['min' => 0, 'max' => 40]),
                ],
                'country' => [
                    new Type(['type' => 'string']),
                    new Choice(['CZ', 'SK']),
                ],
            ],
        ]));

        if (count($violations) > 0) {
            throw new PohodaInvalidDataException($violations);
        }
    }
}
