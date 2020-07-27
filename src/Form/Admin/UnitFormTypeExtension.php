<?php

declare(strict_types=1);

namespace App\Form\Admin;

use Shopsys\FrameworkBundle\Form\Admin\Product\Unit\UnitFormType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints;

class UnitFormTypeExtension extends AbstractTypeExtension
{
    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('pohodaName', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Constraints\Length(['max' => 255, 'maxMessage' => 'Název z pohody nemůže být delší než {{ limit }} znaků.']),
                ],
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        yield UnitFormType::class;
    }
}
