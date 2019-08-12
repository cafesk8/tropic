<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Form\Admin;

use Shopsys\FrameworkBundle\Form\Admin\Advert\AdvertFormType;
use Shopsys\FrameworkBundle\Form\DisplayOnlyType;
use Shopsys\FrameworkBundle\Form\GroupType;
use Shopsys\FrameworkBundle\Form\ProductsType;
use Shopsys\FrameworkBundle\Form\Transformers\RemoveDuplicatesFromArrayTransformer;
use Shopsys\FrameworkBundle\Model\Advert\Advert;
use Shopsys\ShopBundle\Model\Advert\AdvertData;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Constraints\Length;

class AdvertFormTypeExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $settingsGroup = $builder->get('settings');

        $settingsGroup->add('type', ChoiceType::class, [
            'required' => true,
            'choices' => [
                t('Image with link') => Advert::TYPE_IMAGE,
            ],
            'expanded' => true,
            'multiple' => false,
            'data' => Advert::TYPE_IMAGE,
            'constraints' => [
                new Constraints\NotBlank(['message' => 'Please choose advertisement type']),
            ],
            'label' => t('Type'),
            'attr' => [
                'container_class' => 'display-none',
            ],
        ]);

        $settingsGroup->add('name', TextType::class, [
            'required' => true,
            'constraints' => [
                new Constraints\NotBlank(['message' => 'Vyplňte prosím text tlačítka']),
            ],
            'label' => t('Text tlačítka'),
        ]);

        $imagesGroup = $builder->get('image_group');
        $imagesGroup->add('imageSizes', DisplayOnlyType::class, [
            'data' => t('Nahrávejte velikosti obrázků: čtverec (380x290px), obdélník na výšku (380x600px)'),
            'label' => t('Velikost obrázků'),
        ]);

        $builderSixPositionSettingGroup = $this->createGroupForSixPosition($builder);

        $builder->add($builderSixPositionSettingGroup);
    }

    /**
     * @inheritDoc
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AdvertData::class,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return AdvertFormType::class;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @return \Symfony\Component\Form\FormBuilderInterface
     */
    private function createGroupForSixPosition(FormBuilderInterface $builder): FormBuilderInterface
    {
        $builderSixPositionSettingGroup = $builder->create('six_position_setting', GroupType::class, [
            'label' => t('Nastavení pro 6. pozici'),
            'position' => ['before' => 'image_group'],
        ]);

        $builderSixPositionSettingGroup
            ->add('smallTitle', TextType::class, [
                'required' => false,
                'label' => t('Tenký nadpis'),
                'constraints' => [
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Hodnota nemůže byt delší než {{ limit }} znaků',
                    ]),
                ],
            ])
            ->add('bigTitle', TextType::class, [
                'required' => false,
                'label' => t('Tučný nadpis'),
                'constraints' => [
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Hodnota nemůže byt delší než {{ limit }} znaků',
                    ]),
                ],
            ])
            ->add('productTitle', TextType::class, [
                'required' => false,
                'label' => t('Nadpis produktů'),
                'constraints' => [
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Hodnota nemůže byt delší než {{ limit }} znaků',
                    ]),
                ],
            ])
            ->add('products', ProductsType::class, [
                'required' => false,
                'sortable' => true,
                'label' => t('Produkty'),
            ])
            ->addModelTransformer(new RemoveDuplicatesFromArrayTransformer());

        return $builderSixPositionSettingGroup;
    }
}
