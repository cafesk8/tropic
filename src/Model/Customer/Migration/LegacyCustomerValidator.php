<?php

declare(strict_types=1);

namespace App\Model\Customer\Migration;

use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserUpdateData;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class LegacyCustomerValidator
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
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserUpdateData $customerUserUpdateData
     */
    public function validate(CustomerUserUpdateData $customerUserUpdateData): void
    {
        $violations = $this->validator->validate($this->convertToArray($customerUserUpdateData), new Collection([
            'allowExtraFields' => true,
            'fields' => [
                'firstName' => [
                    new Type(['type' => 'string']),
                    new Length(['min' => 0, 'max' => 60]),
                ],
                'lastName' => [
                    new Type(['type' => 'string']),
                    new Length(['min' => 0, 'max' => 30]),
                ],
                'email' => [
                    new Type(['type' => 'string']),
                    new Length(['min' => 0, 'max' => 50]),
                ],
                'telephone' => [
                    new Type(['type' => 'string']),
                    new Length(['min' => 0, 'max' => 30]),
                ],
                'billingAddressData' => [
                    new LegacyBillingAddressConstraint(),
                ],
                'deliveryAddressData' => [
                    new LegacyDeliveryAddressConstraint(),
                ],
            ],
        ]));

        if (count($violations) > 0) {
            throw new LegacyCustomerConstraintViolation($violations);
        }
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserUpdateData $customerUserUpdateData
     */
    private function convertToArray(CustomerUserUpdateData $customerUserUpdateData)
    {
        $customerUpdateDataArray = [
            'firstName' => $customerUserUpdateData->customerUserData->firstName,
            'lastName' => $customerUserUpdateData->customerUserData->lastName,
            'email' => $customerUserUpdateData->customerUserData->email,
            'telephone' => $customerUserUpdateData->customerUserData->telephone,
            'billingAddressData' => (array)$customerUserUpdateData->billingAddressData,
            'deliveryAddressData' => (array)$customerUserUpdateData->deliveryAddressData,
        ];

        return $customerUpdateDataArray;
    }
}
