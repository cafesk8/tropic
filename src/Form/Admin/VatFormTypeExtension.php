<?php

declare(strict_types=1);

namespace App\Form\Admin;

use Shopsys\FrameworkBundle\Form\Admin\Vat\VatFormType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class VatFormTypeExtension extends AbstractTypeExtension
{
    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('pohodaId', IntegerType::class);
        $builder->add('pohodaName', TextType::class);
    }

    /**
     * @inheritDoc
     */
    public function getExtendedType()
    {
        return VatFormType::class;
    }
}
