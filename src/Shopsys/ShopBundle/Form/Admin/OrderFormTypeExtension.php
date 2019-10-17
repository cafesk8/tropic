<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Form\Admin;

use MPAPI\Entity\Order;
use Shopsys\FrameworkBundle\Form\Admin\Order\OrderFormType;
use Shopsys\FrameworkBundle\Form\DisplayOnlyType;
use Shopsys\FrameworkBundle\Twig\DateTimeFormatterExtension;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

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
