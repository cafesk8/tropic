<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Customer\Transfer;

use Shopsys\ShopBundle\Component\Transfer\Exception\TransferInvalidDataException;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Email;
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
     */
    public function validate(CustomerTransferResponseItemData $customerTransferResponseItemData)
    {
        $violations = $this->validator->validate($customerTransferResponseItemData, new Collection([
            'allowExtraFields' => true,
            'fields' => [
                'transferId' => [
                    new NotBlank(),
                    new Type(['type' => 'string']),
                ],
                'countryCode' => [
                    new NotBlank(),
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
                'email' => [
                    new NotBlank(),
                    new Type(['type' => 'string']),
                    new Length(['max' => 255]),
                    new Email(),
                ],
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

    /**
     * @return \Symfony\Component\Validator\Validator\ValidatorInterface
     */
    public function getValidator(): ValidatorInterface
    {
        return $this->validator;
    }
}
