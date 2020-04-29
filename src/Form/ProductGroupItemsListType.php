<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\Transformers\ProductGroupItemsTypeTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductGroupItemsListType extends AbstractType
{
    /**
     * @var \App\Form\Transformers\ProductGroupItemsTypeTransformer
     */
    private $productGroupItemsTypeTransformer;

    /**
     * @param \App\Form\Transformers\ProductGroupItemsTypeTransformer $productGroupItemsTypeTransformer
     */
    public function __construct(ProductGroupItemsTypeTransformer $productGroupItemsTypeTransformer)
    {
        $this->productGroupItemsTypeTransformer = $productGroupItemsTypeTransformer;
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['products'] = $form->getData();
        $view->vars['main_product'] = $options['main_product'];
        $view->vars['top_info_title'] = $options['top_info_title'];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer($this->productGroupItemsTypeTransformer);
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'main_product' => null,
            'top_info_title' => '',
        ]);
    }
}
