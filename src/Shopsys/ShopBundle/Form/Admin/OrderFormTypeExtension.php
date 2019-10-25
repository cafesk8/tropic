<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Form\Admin;

use MPAPI\Entity\Order;
use Shopsys\FrameworkBundle\Form\Admin\Order\OrderFormType;
use Shopsys\FrameworkBundle\Form\DisplayOnlyType;
use Shopsys\FrameworkBundle\Twig\DateTimeFormatterExtension;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;

class OrderFormTypeExtension extends AbstractTypeExtension
{
    /**
     * @var \Shopsys\FrameworkBundle\Twig\DateTimeFormatterExtension
     */
    private $dateTimeFormatterExtension;

    /**
     * @param \Shopsys\FrameworkBundle\Twig\DateTimeFormatterExtension $dateTimeFormatterExtension
     */
    public function __construct(DateTimeFormatterExtension $dateTimeFormatterExtension)
    {
        $this->dateTimeFormatterExtension = $dateTimeFormatterExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var \Shopsys\ShopBundle\Model\Order\Order $order */
        $order = $options['order'];

        $builderBasicInformationGroup = $builder->get('basicInformationGroup');
        $builderBasicInformationGroup
            ->add('exportStatus', DisplayOnlyType::class, [
                'label' => t('Stav přenosu do IS'),
                'data' => $order->getExportStatusName(),
                'position' => [
                    'after' => 'status',
                ],
            ]);
        $builderBasicInformationGroup
            ->add('exportedAt', DisplayOnlyType::class, [
                'label' => t('Datum přenosu do IS'),
                'data' => $this->dateTimeFormatterExtension->formatDateTime($order->getExportedAt()),
                'position' => [
                    'after' => 'exportStatus',
                ],
            ]);

        if ($order !== null && $order->getMallOrderId() !== null) {
            $builderBasicInformationGroup
                ->add('mallStatus', ChoiceType::class, [
                    'label' => t('Mall status'),
                    'choices' => $this->getPossibleMallStatus($order->getMallStatus()),
                    'position' => [
                        'after' => 'status',
                    ],
                ]);
        }

        if ($order !== null) {
            $builderBasicInformationGroup
                ->add('payment', DisplayOnlyType::class, [
                    'label' => t('Typ platby'),
                    'data' => $order->getPayment()->getName(),
                ]);

            if ($order->getPayment()->isGoPay() === true) {
                $builderBasicInformationGroup
                    ->add('gopayStatus', DisplayOnlyType::class, [
                        'label' => t('Stav platby GoPay'),
                        'data' => $order->getGoPayStatus(),
                    ]);
            }
        }
        $builderBasicInformationGroup
            ->add('trackingNumber', TextType::class, [
                'label' => t('Číslo pro sledování zásilky'),
                'required' => false,
            ]);

        $this->extendConstraintsOfBillingDataGroup($builder->get('billingDataGroup'));
        $this->extendConstraintsOfShippingDataGroup($builder->get('shippingAddressGroup')->get('deliveryAddressFields'));
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     */
    private function extendConstraintsOfBillingDataGroup(FormBuilderInterface $builder): void
    {
        $codeFieldOptions = $builder->get('street')->getOptions();
        $codeFieldOptions['required'] = false;
        $codeFieldOptions['constraints'] = [
            new Length([
                'max' => 100,
                'maxMessage' => 'Street name cannot be longer than {{ limit }} characters',
            ]),
        ];
        $codeFieldType = get_class($builder->get('street')->getType()->getInnerType());
        $builder->add('street', $codeFieldType, $codeFieldOptions);

        $codeFieldOptions = $builder->get('city')->getOptions();
        $codeFieldOptions['required'] = false;
        $codeFieldOptions['constraints'] = [
            new Length([
                'max' => 100,
                'maxMessage' => 'City name cannot be longer than {{ limit }} characters',
            ]),
        ];
        $codeFieldType = get_class($builder->get('city')->getType()->getInnerType());
        $builder->add('city', $codeFieldType, $codeFieldOptions);

        $codeFieldOptions = $builder->get('postcode')->getOptions();
        $codeFieldOptions['required'] = false;
        $codeFieldOptions['constraints'] = [
            new Length([
                'max' => 6,
                'maxMessage' => 'Zip code cannot be longer than {{ limit }} characters',
            ]),
        ];
        $codeFieldType = get_class($builder->get('postcode')->getType()->getInnerType());
        $builder->add('postcode', $codeFieldType, $codeFieldOptions);
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     */
    private function extendConstraintsOfShippingDataGroup(FormBuilderInterface $builder): void
    {
        $codeFieldOptions = $builder->get('deliveryPostcode')->getOptions();
        $codeFieldOptions['constraints'] = [
            new Length([
                'max' => 6,
                'maxMessage' => 'Zip code cannot be longer than {{ limit }} characters',
            ]),
        ];
        $codeFieldType = get_class($builder->get('deliveryPostcode')->getType()->getInnerType());
        $builder->add('deliveryPostcode', $codeFieldType, $codeFieldOptions);
    }

    /**
     * @param string|null $currentStatus
     * @return string[]
     */
    private function getPossibleMallStatus(?string $currentStatus): array
    {
        if ($currentStatus === null) {
            return [];
        }

        if ($currentStatus === Order::STATUS_OPEN) {
            return [
                t('Otevřena') => Order::STATUS_OPEN,
                t('Zrušena') => Order::STATUS_CANCELLED,
                t('Odesílána') => Order::STATUS_SHIPPING,
            ];
        }
        if ($currentStatus === Order::STATUS_SHIPPING) {
            return [
                t('Odesílána') => Order::STATUS_SHIPPING,
                t('Zrušena') => Order::STATUS_CANCELLED,
                t('Odeslána') => Order::STATUS_SHIPPED,
            ];
        }
        if ($currentStatus === Order::STATUS_SHIPPED) {
            // Order::STATUS_RETURNED
            return [
                t('Odeslána') => Order::STATUS_SHIPPED,
                t('Nedoručena') => Order::STATUS_RETURNED,
            ];
        }
        if ($currentStatus === Order::STATUS_RETURNED) {
            return [
                t('Nedoručena') => Order::STATUS_RETURNED,
            ];
        }
        if ($currentStatus === Order::STATUS_CANCELLED) {
            return [
                t('Zrušena') => Order::STATUS_CANCELLED,
            ];
        }
        if ($currentStatus === Order::STATUS_DELIVERED) {
            return [
                t('Doručena') => Order::STATUS_DELIVERED,
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return OrderFormType::class;
    }
}
