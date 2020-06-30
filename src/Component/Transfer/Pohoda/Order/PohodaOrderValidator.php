<?php

declare(strict_types=1);

namespace App\Component\Transfer\Pohoda\Order;

use App\Component\Transfer\Pohoda\Customer\PohodaAddressConstraint;
use App\Component\Transfer\Pohoda\Customer\PohodaShipToAddressConstraint;
use App\Component\Transfer\Pohoda\Exception\PohodaInvalidDataException;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PohodaOrderValidator
{
    /**
     * @var \Symfony\Component\Validator\Validator\ValidatorInterface
     */
    private ValidatorInterface $validator;

    /**
     * @param \Symfony\Component\Validator\Validator\ValidatorInterface $validator
     */
    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param \App\Component\Transfer\Pohoda\Order\PohodaOrder $pohodaOrder
     */
    public function validate(PohodaOrder $pohodaOrder): void
    {
        $violations = $this->validator->validate($pohodaOrder->getAsArray(), new Collection([
            'allowExtraFields' => true,
            'fields' => [
                'number' => [
                    new Type(['type' => 'string']),
                    new Length(['max' => 16]),
                ],
                'dataPackItemId' => [
                    new Type(['type' => 'string']),
                    new Length(['min' => 10, 'max' => 100]),
                ],
                'pricingGroup' => [
                    new Type(['type' => 'string']),
                    new Length([
                        'min' => 0,
                        'max' => 10,
                        'maxMessage' => 'Identifikátor cenové skupiny musí obsahovat maximálně 10 znaků',
                    ]),
                ],
                'address' => [
                    new PohodaAddressConstraint(),
                ],
                'shipToAddress' => [
                    new PohodaShipToAddressConstraint(),
                ],
                'orderItems' => [
                    new PohodaOrderItemsConstraint(),
                ],
            ],
        ]));

        if (count($violations) > 0) {
            throw new PohodaInvalidDataException($violations);
        }
    }
}
