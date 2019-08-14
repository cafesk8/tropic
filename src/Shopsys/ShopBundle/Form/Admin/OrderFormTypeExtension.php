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
                    'choices' => [
                        t('Zrušena, nefakturována') => Order::STATUS_CANCELLED,
                        t('Doručena, vyfakturována') => Order::STATUS_DELIVERED,
                        t('Otevřena') => Order::STATUS_OPEN,
                        t('Nedoručena, nefakturována') => Order::STATUS_RETURNED,
                        t('Odeslána, nefakturována') => Order::STATUS_SHIPPED,
                        t('Odesílána, nefakturována') => Order::STATUS_SHIPPING,
                    ],
                    'position' => [
                        'after' => 'status',
                    ],
                ]);
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
