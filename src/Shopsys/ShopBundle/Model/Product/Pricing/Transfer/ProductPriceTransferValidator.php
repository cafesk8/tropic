<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Pricing\Transfer;

use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\ShopBundle\Component\Transfer\Exception\TransferInvalidDataException;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductPriceTransferValidator
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
     * @param \Shopsys\ShopBundle\Model\Product\Transfer\ProductTransferResponseItemData $productPriceTransferResponseItemData
     */
    public function validate(ProductPriceTransferResponseItemData $productPriceTransferResponseItemData)
    {
        $violations = $this->validator->validate($productPriceTransferResponseItemData, new Collection([
            'allowExtraFields' => true,
            'fields' => [
                'number' => [
                    new NotBlank(),
                    new Type(['type' => 'string']),
                ],
                'barcode' => [
                    new NotBlank(),
                    new Type(['type' => 'string']),
                ],
                'price' => [
                    new NotBlank(),
                    new Type(['type' => Money::class]),
                ],
            ],
        ]));

        if (count($violations) > 0) {
            throw new TransferInvalidDataException($violations);
        }
    }
}
