<?php

declare(strict_types=1);

namespace App\Component\Transfer\Pohoda\Customer;

use App\Component\Transfer\Pohoda\Exception\PohodaInvalidDataException;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PohodaCustomerValidator
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
     * @param \App\Component\Transfer\Pohoda\Customer\PohodaCustomer $pohodaCustomer
     */
    public function validate(PohodaCustomer $pohodaCustomer): void
    {
        $violations = $this->validator->validate($pohodaCustomer->getAsArray(), new Collection([
            'allowExtraFields' => true,
            'fields' => [
                'dataPackItemId' => [
                    new Type(['type' => 'string']),
                    new Length(['min' => 10, 'max' => 100]),
                ],
                'address' => [
                    new PohodaAddressConstraint(),
                ],
                'shipToAddress' => [
                    new PohodaShipToAddressConstraint(),
                ],
            ],
        ]));

        if (count($violations) > 0) {
            throw new PohodaInvalidDataException($violations);
        }
    }
}
