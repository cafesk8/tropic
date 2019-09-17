<?php

declare(strict_types = 1);

namespace Shopsys\ShopBundle\Form\Admin;

use Shopsys\ShopBundle\Model\Order\MassAction\OrderMassActionData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderMassActionFormType extends AbstractType
{
    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('selectType', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    t('Pouze zkontrolované objednávky') => OrderMassActionData::SELECT_TYPE_CHECKED,
                    t('All search results') => OrderMassActionData::SELECT_TYPE_ALL_RESULTS,
                ],
            ])
            ->add('action', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    t('CSV Export') => OrderMassActionData::ACTION_CSV_EXPORT,
                ],
            ])
            ->add('submit', SubmitType::class);
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => ['novalidate' => 'novalidate'],
            'data_class' => OrderMassActionData::class,
        ]);
    }
}
