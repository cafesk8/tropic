<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Model\Administrator\Role;
use Shopsys\FrameworkBundle\Form\Admin\Administrator\AdministratorFormType;
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
    public static function getExtendedTypes(): iterable
    {
        yield AdministratorFormType::class;
    }
}
