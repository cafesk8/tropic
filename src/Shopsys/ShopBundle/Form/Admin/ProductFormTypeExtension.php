<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Form\Admin;

use Shopsys\FrameworkBundle\Form\Admin\Product\ProductFormType;
use Shopsys\FrameworkBundle\Form\GroupType;
use Shopsys\FrameworkBundle\Form\ProductsType;
use Shopsys\FrameworkBundle\Form\Transformers\RemoveDuplicatesFromArrayTransformer;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterFacade;
use Shopsys\ShopBundle\Model\Product\Product;
use Shopsys\ShopBundle\Model\Product\ProductData;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class ProductFormTypeExtension extends AbstractTypeExtension
{
    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterFacade
     */
    private $parameterFacade;

    /**
     * ProductFormTypeExtension constructor.
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterFacade $parameterFacade
     */
    public function __construct(ParameterFacade $parameterFacade)
    {
        $this->parameterFacade = $parameterFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $product = $options['product'];
        /* @var $product \Shopsys\FrameworkBundle\Model\Product\Product|null */

        $builderStoreStockGroup = $builder->create('storeStock', GroupType::class, [
            'label' => t('Stock in stores'),
        ]);

        $builderStoreStockGroup->add('stockQuantityByStoreId', StoreStockType::class);

        $builder->add($builderStoreStockGroup);

        if ($product instanceof Product && $product->isMainVariant()) {
            $variantGroup = $builder->get('variantGroup');

            $allParameters = $this->parameterFacade->getAll();
            $variantGroup
                ->add('distinguishingParameter', ChoiceType::class, [
                    'required' => false,
                    'label' => t('Rozlišující parametr'),
                    'choices' => $allParameters,
                    'choice_label' => 'name',
                    'choice_value' => 'id',
                    'placeholder' => t('Zvolte parametr'),
                    'constraints' => [
                        new Constraints\NotBlank(),
                    ],
                ]);

            $builder->add($variantGroup);
        }

        if ($product !== null && $product->getMainVariantGroup() !== null) {
            $this->createMainVariantGroup($builder);
        }
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductData::class,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType(): string
    {
        return ProductFormType::class;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     */
    private function createMainVariantGroup(FormBuilderInterface $builder): void
    {
        $builderMainVariantGroup = $builder->create('builderMainVariantGroup', GroupType::class, [
            'label' => t('Propojené produkty'),
        ]);

        $builderMainVariantGroup
            ->add(
                $builder
                    ->create('productsInGroup', ProductsType::class, [
                        'label' => t('Produkty'),
                        'allow_main_variants' => true,
                        'allow_variants' => false,
                        'is_main_variant_group' => true,
                        'constraints' => [
                            new Constraints\NotBlank(),
                        ],
                    ])
                    ->addModelTransformer(new RemoveDuplicatesFromArrayTransformer())
            );

        $builder->add($builderMainVariantGroup);
    }
}
