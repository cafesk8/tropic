<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Form\Front\Product;

use Shopsys\FrameworkBundle\Form\Constraints\NotNegativeMoneyAmount;
use Shopsys\FrameworkBundle\Twig\MoneyExtension;
use Shopsys\ShopBundle\Model\Product\Filter\ProductFilterConfig;
use Shopsys\ShopBundle\Model\Product\Filter\ProductFilterData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductFilterFormType extends AbstractType
{
    /**
     * @var \Shopsys\FrameworkBundle\Twig\MoneyExtension
     */
    private $moneyExtension;

    /**
     * @param \Shopsys\FrameworkBundle\Twig\MoneyExtension $moneyExtension
     */
    public function __construct(MoneyExtension $moneyExtension)
    {
        $this->moneyExtension = $moneyExtension;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var \Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterConfig $config */
        $config = $options['product_filter_config'];

        $moneyBuilder = $builder->create('money', MoneyType::class);

        $builder
            ->add('minimalPrice', MoneyType::class, [
                'required' => false,
                'attr' => ['placeholder' => $this->moneyExtension->moneyFormatFilter($config->getPriceRange()->getMinimalPrice(), 0)],
                'invalid_message' => 'Please enter price in correct format (positive number with decimal separator)',
                'constraints' => [
                    new NotNegativeMoneyAmount(['message' => 'Price must be greater or equal to zero']),
                ],
                'scale' => 0,
            ])
            ->add('maximalPrice', MoneyType::class, [
                'required' => false,
                'attr' => ['placeholder' => $this->moneyExtension->moneyFormatFilter($config->getPriceRange()->getMaximalPrice(), 0)],
                'invalid_message' => 'Please enter price in correct format (positive number with decimal separator)',
                'constraints' => [
                    new NotNegativeMoneyAmount(['message' => 'Price must be greater or equal to zero']),
                ],
                'scale' => 0,
            ])
            ->add('parameters', ParameterFilterFormType::class, [
                'required' => false,
                'product_filter_config' => $config,
            ])
            ->add('inStock', CheckboxType::class, ['required' => false])
            ->add('flags', ChoiceType::class, [
                'required' => false,
                'choices' => $config->getFlagChoices(),
                'choice_label' => 'name',
                'choice_value' => 'id',
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('brands', ChoiceType::class, [
                'required' => false,
                'choices' => $config->getBrandChoices(),
                'choice_label' => 'name',
                'choice_value' => 'id',
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('search', SubmitType::class);
        $this->addDistinguishingParameters($builder, $config);
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired('product_filter_config')
            ->setAllowedTypes('product_filter_config', ProductFilterConfig::class)
            ->setDefaults([
                'attr' => ['novalidate' => 'novalidate'],
                'data_class' => ProductFilterData::class,
                'method' => 'GET',
                'csrf_protection' => false,
            ]);
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param \Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterConfig $config
     */
    private function addDistinguishingParameters(FormBuilderInterface $builder, ProductFilterConfig $config): void
    {
        $builder->add('colors', ChoiceType::class, [
            'required' => false,
            'choices' => $config->getColorChoices(),
            'choice_label' => 'rgb',
            'choice_name' => 'id',
            'choice_value' => 'id',
            'multiple' => true,
            'expanded' => true,
        ]);

        $builder->add('sizes', ChoiceType::class, [
            'required' => false,
            'choices' => $config->getSizeChoices(),
            'choice_label' => 'text',
            'choice_name' => 'id',
            'choice_value' => 'id',
            'multiple' => true,
            'expanded' => true,
        ]);
    }
}
