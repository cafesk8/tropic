<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Form\Admin;

use Shopsys\FrameworkBundle\Form\GroupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class DeliveryDateSettingFormType extends AbstractType
{
    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addDeadlineGroup($builder);

        $builder->add('save', SubmitType::class);
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     */
    private function addDeadlineGroup(FormBuilderInterface $builder): void
    {
        $deadlineGroup = $builder
            ->create('deadline', GroupType::class, [
                'label' => t('Deadline pro podání objednávky'),
            ]);

        $deadlineGroup
            ->add('hours', IntegerType::class, [
                'required' => true,
                'label' => t('Hodiny'),
                'constraints' => [
                    new Constraints\NotBlank(['message' => 'Vyplňte prosím deadline pro přijímání objednávek']),
                    new Constraints\GreaterThanOrEqual(['value' => 0, 'message' => 'Hodiny musí být v rozmezí od 0 do 23']),
                    new Constraints\LessThan(['value' => 24, 'message' => 'Hodiny musí být v rozmezí od 0 do 23']),
                ],
            ])
            ->add('minutes', IntegerType::class, [
                'required' => true,
                'label' => t('Minuty'),
                'constraints' => [
                    new Constraints\NotBlank(['message' => 'Vyplňte prosím deadline pro přijímání objednávek']),
                    new Constraints\GreaterThanOrEqual(['value' => 0, 'message' => 'Minuty musí být v rozmezí od 0 do 59']),
                    new Constraints\LessThan(['value' => 60, 'message' => 'Minuty musí být v rozmezí od 0 do 59']),
                ],
            ]);

        $builder->add($deadlineGroup);
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => ['novalidate' => 'novalidate'],
        ]);
    }
}
