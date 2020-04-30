<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Model\Order\Status\OrderStatus;
use App\Model\Order\Status\OrderStatusData;
use Shopsys\FrameworkBundle\Form\Admin\Order\Status\OrderStatusFormType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderStatusFormTypeExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('transferStatus', TextType::class, [
            'label' => t('Id stavu z IS'),
        ]);

        $builder->add('smsAlertType', ChoiceType::class, [
            'placeholder' => t('Bez SMS alertu'),
            'choices' => [
                t('SMS alert 5 dní do vyzvednutí') => OrderStatus::SMS_ALERT_5_DAY_BEFORE,
                t('SMS alert 2 dny do vyzvednutí') => OrderStatus::SMS_ALERT_2_DAY_BEFORE,
            ],
            'label' => t('Typ sms alertu'),
        ]);

        $builder->add('activatesGiftCertificates', CheckboxType::class, [
            'label' => 'Aktivuje dárkové certifikáty',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        yield OrderStatusFormType::class;
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => OrderStatusData::class,
            'attr' => ['novalidate' => 'novalidate'],
        ]);
    }
}
