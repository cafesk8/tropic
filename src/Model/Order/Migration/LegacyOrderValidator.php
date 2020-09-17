<?php

declare(strict_types=1);

namespace App\Model\Order\Migration;

use App\Model\Order\Order;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class LegacyOrderValidator
{
    public const FIRST_NAME_LENGTH = 60;
    public const LAST_NAME_LENGTH = 30;

    private ValidatorInterface $validator;

    /**
     * @param \Symfony\Component\Validator\Validator\ValidatorInterface $validator
     */
    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param \App\Model\Order\Order $order
     * @param \Shopsys\FrameworkBundle\Component\Money\Money $totalLegacyOrderPriceWithVat
     * @param \Shopsys\FrameworkBundle\Component\Money\Money $totalLegacyOrderPriceWithoutVat
     * @param \Shopsys\FrameworkBundle\Component\Money\Money $legacyOrderPriceWithVat
     * @throws \App\Model\Order\Migration\LegacyOrderConstraintViolation
     */
    public function validate(Order $order, Money $totalLegacyOrderPriceWithVat, Money $totalLegacyOrderPriceWithoutVat, Money $legacyOrderPriceWithVat): void
    {
        $orderAsArray = $this->convertToArray($order);
        $violations = $this->validator->validate($orderAsArray, new Collection([
            'allowExtraFields' => true,
            'fields' => [
                'firstName' => [
                    new Type(['type' => 'string']),
                    new Length(['min' => 0, 'max' => self::FIRST_NAME_LENGTH]),
                    new NotBlank(),
                ],
                'lastName' => [
                    new Type(['type' => 'string']),
                    new Length(['min' => 0, 'max' => self::LAST_NAME_LENGTH]),
                    new NotBlank(),
                ],
                'email' => [
                    new Type(['type' => 'string']),
                    new Length(['min' => 0, 'max' => 50]),
                    new NotBlank(),
                ],
                'telephone' => [
                    new Type(['type' => 'string']),
                    new Length(['min' => 0, 'max' => 20]),
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
                'street' => [
                    new Type(['type' => 'string']),
                    new Length(['min' => 0, 'max' => 100]),
                    new NotBlank(),
                ],
                'city' => [
                    new Type(['type' => 'string']),
                    new Length(['min' => 0, 'max' => 100]),
                    new NotBlank(),
                ],
                'postcode' => [
                    new Type(['type' => 'string']),
                    new Length(['min' => 0, 'max' => 6]),
                    new NotBlank(),
                ],
                'deliveryFirstName' => [
                    new Type(['type' => 'string']),
                    new Length(['min' => 0, 'max' => self::FIRST_NAME_LENGTH]),
                    new NotBlank(),
                ],
                'deliveryLastName' => [
                    new Type(['type' => 'string']),
                    new Length(['min' => 0, 'max' => self::LAST_NAME_LENGTH]),
                    new NotBlank(),
                ],
                'deliveryPostcode' => [
                    new Type(['type' => 'string']),
                    new Length(['min' => 0, 'max' => 6]),
                    new NotBlank(),
                ],
                'deliveryCompanyName' => [
                    new Type(['type' => 'string']),
                    new Length(['min' => 0, 'max' => 100]),
                ],
                'deliveryTelephone' => [
                    new Type(['type' => 'string']),
                    new Length(['min' => 0, 'max' => 30]),
                ],
                'deliveryStreet' => [
                    new Type(['type' => 'string']),
                    new Length(['min' => 0, 'max' => 100]),
                    new NotBlank(),
                ],
                'deliveryCity' => [
                    new Type(['type' => 'string']),
                    new Length(['min' => 0, 'max' => 100]),
                    new NotBlank(),
                ],
                'totalOrderPriceWithVat' => [
                    new Callback([
                        'callback' => [$this, 'validateOrderPriceWithVatToLegacyPrice'],
                        'payload' => [
                            'totalLegacyOrderPriceWithVat' => $totalLegacyOrderPriceWithVat,
                            'legacyOrderPriceWithVat' => $legacyOrderPriceWithVat,
                        ],
                    ]),
                ],
                'totalOrderPriceWithoutVat' => [
                    new Callback([
                        'callback' => [$this, 'validateOrderPriceWithoutVatToLegacyPrice'],
                        'payload' => $totalLegacyOrderPriceWithoutVat,
                    ]),
                ],
            ],
        ]));

        if (count($violations) > 0) {
            throw new LegacyOrderConstraintViolation($violations);
        }
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Money\Money $totalOrderPriceWithVat
     * @param \Symfony\Component\Validator\Context\ExecutionContextInterface $context
     * @param \Shopsys\FrameworkBundle\Component\Money\Money[] $legacyPrices
     */
    public function validateOrderPriceWithVatToLegacyPrice(Money $totalOrderPriceWithVat, ExecutionContextInterface $context, array $legacyPrices): void
    {
        $totalLegacyOrderPriceWithVat = $legacyPrices['totalLegacyOrderPriceWithVat'];
        $legacyOrderPriceWithVat = $legacyPrices['legacyOrderPriceWithVat'];
        if (!$totalOrderPriceWithVat->equals($totalLegacyOrderPriceWithVat) && !$totalLegacyOrderPriceWithVat->isZero()) {
            $context->addViolation(
                sprintf('Nová cena s DPH (%s) je rozdílná oproti celkové ceně v starém e-shopu (%s)', $totalOrderPriceWithVat->getAmount(), $totalLegacyOrderPriceWithVat->getAmount())
            );
        } elseif ($totalLegacyOrderPriceWithVat->isZero() && !$legacyOrderPriceWithVat->isZero() && !$totalOrderPriceWithVat->equals($legacyOrderPriceWithVat)) {
            $context->addViolation(
                sprintf('Nová cena s DPH (%s) je rozdílná oproti celkové ceně v starém e-shopu (%s) - použito orderPrice', $totalOrderPriceWithVat->getAmount(), $legacyOrderPriceWithVat->getAmount())
            );
        }
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Money\Money $totalOrderPriceWithoutVat
     * @param \Symfony\Component\Validator\Context\ExecutionContextInterface $context
     * @param \Shopsys\FrameworkBundle\Component\Money\Money $totalLegacyOrderPriceWithoutVat
     */
    public function validateOrderPriceWithoutVatToLegacyPrice(Money $totalOrderPriceWithoutVat, ExecutionContextInterface $context, Money $totalLegacyOrderPriceWithoutVat): void
    {
        if (!$totalOrderPriceWithoutVat->equals($totalLegacyOrderPriceWithoutVat) && !$totalLegacyOrderPriceWithoutVat->isZero()) {
            $context->addViolation(
                sprintf('Nová cena bez DPH (%s) je rozdílná oproti celkové ceně v starém e-shopu (%s)', $totalOrderPriceWithoutVat->getAmount(), $totalLegacyOrderPriceWithoutVat->getAmount())
            );
        }
    }

    /**
     * @param \App\Model\Order\Order $order
     * @return array
     */
    private function convertToArray(Order $order): array
    {
        return [
            'firstName' => $order->getFirstName(),
            'lastName' => $order->getLastName(),
            'email' => $order->getEmail(),
            'telephone' => $order->getTelephone(),
            'companyName' => $order->getCompanyName(),
            'companyNumber' => $order->getCompanyNumber(),
            'companyTaxNumber' => $order->getCompanyTaxNumber(),
            'street' => $order->getStreet(),
            'city' => $order->getCity(),
            'postcode' => $order->getPostcode(),
            'deliveryFirstName' => $order->getDeliveryFirstName(),
            'deliveryLastName' => $order->getDeliveryLastName(),
            'deliveryPostcode' => $order->getDeliveryPostcode(),
            'deliveryCompanyName' => $order->getDeliveryCompanyName(),
            'deliveryTelephone' => $order->getDeliveryTelephone(),
            'deliveryStreet' => $order->getDeliveryStreet(),
            'deliveryCity' => $order->getDeliveryCity(),
            'totalOrderPriceWithVat' => $order->getTotalPriceWithVat(),
            'totalOrderPriceWithoutVat' => $order->getTotalPriceWithoutVat(),
        ];
    }
}
