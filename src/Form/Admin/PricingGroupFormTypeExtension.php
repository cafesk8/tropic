<?php

declare(strict_types=1);

namespace App\Form\Admin;

use Shopsys\FrameworkBundle\Form\Admin\Pricing\Group\PricingGroupFormType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints;

class PricingGroupFormTypeExtension extends AbstractTypeExtension
{
    /**
     * @inheritDoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('discount', NumberType::class, [
            'constraints' => [
                new Constraints\GreaterThanOrEqual(['value' => 0]),
                new Constraints\NotBlank(),
            ],
            'label' => t('Sleva %'),
            'required' => true,
        ]);
    }

    /**
     * @inheritDoc
     */
    public static function getExtendedTypes(): iterable
    {
        yield PricingGroupFormType::class;
    }
}
