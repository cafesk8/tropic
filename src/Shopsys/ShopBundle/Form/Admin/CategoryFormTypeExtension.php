<?php

namespace Shopsys\ShopBundle\Form\Admin;

use Shopsys\FormTypesBundle\YesNoType;
use Shopsys\FrameworkBundle\Form\Admin\Category\CategoryFormType;
use Shopsys\ShopBundle\Model\Category\CategoryData;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryFormTypeExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->get('settings')
            ->add('displayedInHorizontalMenu', YesNoType::class, [
                'required' => false,
                'label' => t('V hlavním menu'),
            ]);
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'data_class' => CategoryData::class,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return CategoryFormType::class;
    }
}
