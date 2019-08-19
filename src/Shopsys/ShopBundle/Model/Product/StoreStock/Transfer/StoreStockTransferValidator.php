<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\StoreStock\Transfer;

use Shopsys\ShopBundle\Component\Transfer\Exception\TransferInvalidDataException;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class StoreStockTransferValidator
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
     * @param \Shopsys\ShopBundle\Model\Product\Transfer\ProductTransferResponseItemData $storeStockTransferResponseItemData
     */
    public function validate(StoreStockTransferResponseItemData $storeStockTransferResponseItemData)
    {
        $violations = $this->validator->validate($storeStockTransferResponseItemData, new Collection([
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
                'sitesQuantity' => [
                    new All([
                        new Collection([
                            'siteNumber' => [
                                new NotBlank(),
                                new Type(['type' => 'string']),
                            ],
                            'quantity' => [
                                new NotBlank(),
                            ],
                        ]),
                    ]),
                ],
            ],
        ]));

        if (count($violations) > 0) {
            throw new TransferInvalidDataException($violations);
        }
    }
}
