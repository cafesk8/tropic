<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Transfer;

use Shopsys\ShopBundle\Component\Transfer\Exception\TransferInvalidDataException;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductTransferValidator
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
     * @param \Shopsys\ShopBundle\Model\Product\Transfer\ProductTransferResponseItemData $productTransferResponseItemData
     */
    public function validate(ProductTransferResponseItemData $productTransferResponseItemData)
    {
        $violations = $this->validator->validate($productTransferResponseItemData, new Collection([
            'allowExtraFields' => true,
            'fields' => [
                'number' => [
                    new NotBlank(),
                    new Type(['type' => 'int']),
                ],
                'name' => [
                    new NotBlank(),
                    new Type(['type' => 'string']),
                    new Length(['max' => 255]),
                ],
                'description' => [
                    new Type(['type' => 'string']),
                ],
            ],
        ]));

        if (count($violations) > 0) {
            throw new TransferInvalidDataException($violations);
        }
    }
}
