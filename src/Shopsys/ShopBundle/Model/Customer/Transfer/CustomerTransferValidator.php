<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Customer\Transfer;

use Shopsys\FrameworkBundle\Form\Constraints\UniqueEmail;
use Shopsys\ShopBundle\Component\Transfer\Exception\TransferInvalidDataException;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CustomerTransferValidator
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
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @param \Shopsys\ShopBundle\Model\Customer\Transfer\CustomerTransferResponseItemData $customerTransferResponseItemData
     * @param bool $isNewCustomer
     */
    public function validate(CustomerTransferResponseItemData $customerTransferResponseItemData, bool $isNewCustomer)
    {
        $emailConstraints = [
            new NotBlank(),
            new Type(['type' => 'string']),
            new Length(['max' => 255]),
        ];

        if ($isNewCustomer === true) {
            $emailConstraints[] = new UniqueEmail(['domainId' => $customerTransferResponseItemData->getDomainId()]);
        }

        $violations = $this->validator->validate($customerTransferResponseItemData, new Collection([
            'allowExtraFields' => true,
            'fields' => [
                'transferId' => [
                    new NotBlank(),
                    new Type(['type' => 'string']),
                ],
                'countryCode' => [
                    new NotBlank(),
                    new Choice(['choices' => ['CZ', 'SK', 'DE']]),
                ],
                'firstName' => [
                    new NotBlank(),
                    new Type(['type' => 'string']),
                    new Length(['max' => 255]),
                ],
                'lastName' => [
                    new NotBlank(),
                    new Type(['type' => 'string']),
                    new Length(['max' => 255]),
                ],
                'email' => $emailConstraints,
                'phone' => [
                    new Type(['type' => 'string']),
                ],
                'branchNumber' => [
                    new Type(['type' => 'string']),
                ],
            ],
        ]));

        if (count($violations) > 0) {
            throw new TransferInvalidDataException($violations);
        }
    }
}
