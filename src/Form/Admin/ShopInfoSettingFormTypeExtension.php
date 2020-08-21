<?php

declare(strict_types=1);

namespace App\Form\Admin;

use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Shopsys\FrameworkBundle\Form\Admin\ShopInfo\ShopInfoSettingFormType;
use Shopsys\FrameworkBundle\Form\GroupType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

class ShopInfoSettingFormTypeExtension extends AbstractTypeExtension
{
    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builderPublishedDataGroup = $builder->get('publishedData');
        $builderPublishedDataGroup = $builder->create('store', GroupType::class, [
            'label' => t('Prodejna'),
        ])
        ->add('openingHours', CKEditorType::class, [
            'required' => false,
            'label' => t('Opening hours'),
        ]);

        $builder->add($builderPublishedDataGroup);
    }

    /**
     * @return string[]
     */
    public static function getExtendedTypes(): iterable
    {
        yield ShopInfoSettingFormType::class;

    }
}