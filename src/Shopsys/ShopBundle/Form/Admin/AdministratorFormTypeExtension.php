<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Form\Admin;

use Shopsys\FrameworkBundle\Form\Admin\Administrator\AdministratorFormType;
use Shopsys\ShopBundle\Model\Administrator\Role;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class AdministratorFormTypeExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builderSettingsGroup = $builder->get('settings');
        $builderSettingsGroup->add('roles', ChoiceType::class, [
            'label' => t('Administrátor má právo'),
            'multiple' => true,
            'expanded' => true,
            'required' => false,
            'choices' => Role::getAllRolesIndexedByTitles(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return AdministratorFormType::class;
    }
}
