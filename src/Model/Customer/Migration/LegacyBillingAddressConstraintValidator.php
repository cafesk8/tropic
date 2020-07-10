<?php

declare(strict_types=1);

namespace App\Model\Customer\Migration;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class LegacyBillingAddressConstraintValidator extends ConstraintValidator
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
     * @param array $legacyBillingAddress
     * @param \Symfony\Component\Validator\Constraint $constraint
     */
    public function validate($legacyBillingAddress, Constraint $constraint): void
    {
        $violations = $this->validator->validate($legacyBillingAddress, new Collection([
            'allowExtraFields' => true,
            'fields' => [
                'street' => [
                    new Type(['type' => 'string']),
                    new Length(['min' => 0, 'max' => 100]),
                ],
                'city' => [
                    new Type(['type' => 'string']),
                    new Length(['min' => 0, 'max' => 100]),
                ],
                'postcode' => [
                    new Type(['type' => 'string']),
                    new Length(['min' => 0, 'max' => 6]),
                    new NotBlank(),
                ],
                'companyName' => [
                    new Type(['type' => 'string']),
                    new Length(['min' => 0, 'max' => 100]),
                ],
                'companyNumber' => [
                    new Type(['type' => 'string']),
                    new Length(['min' => 0, 'max' => 20]),
                ],
                'companyTaxNumber' => [
                    new Type(['type' => 'string']),
                    new Length(['min' => 0, 'max' => 30]),
                ],
            ],
        ]));

        if (count($violations) > 0) {
            foreach ($violations as $violation) {
                $this->context->addViolation(sprintf('Nevalidní hodnota pro %s - "%s"', $violation->getPropertyPath(), $violation->getMessage()));
            }
        }
    }
}
