<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Form\Admin;

use Ivory\CKEditorBundle\Form\Type\CKEditorType;
use Shopsys\FormTypesBundle\YesNoType;
use Shopsys\FrameworkBundle\Form\ValidationGroup;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class InfoRowFormType extends AbstractType
{
    public const VALIDATION_GROUP_IS_VISIBLE = 'isVisible';

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('visibility', YesNoType::class, [
                    'required' => false,
                    'label' => t('Zobrazovat'),
                ])
            ->add('text', CKEditorType::class, [
                    'required' => false,
                    'label' => t('Text'),
                    'constraints' => [
                        new NotBlank([
                            'message' => t('Pokud chcete informační řádek uživatelům zobrazit, musíte jej vyplnit'),
                            'groups' => [self::VALIDATION_GROUP_IS_VISIBLE],
                        ]),
                    ],
                ])
            ->add('save', SubmitType::class);
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => ['novalidate' => 'novalidate'],
            'validation_groups' => static function (FormInterface $form) {
                $validationGroups = [ValidationGroup::VALIDATION_GROUP_DEFAULT];

                $formData = $form->getData();
                if ($formData['visibility'] === true) {
                    $validationGroups[] = self::VALIDATION_GROUP_IS_VISIBLE;
                }

                return $validationGroups;
            },
        ]);
    }
}
