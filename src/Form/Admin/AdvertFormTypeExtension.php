<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Model\Advert\AdvertData;
use Shopsys\FrameworkBundle\Form\Admin\Advert\AdvertFormType;
use Shopsys\FrameworkBundle\Form\DisplayOnlyType;
use Shopsys\FrameworkBundle\Model\Advert\Advert;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

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
            'data' => t('Pro plochy 1, 2, 3 - čtverec (380x230px), pro plochu 4 - velký obdelník na šířku (1180x387px).'),
            'label' => t('Velikost obrázků'),
        ]);
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
}
