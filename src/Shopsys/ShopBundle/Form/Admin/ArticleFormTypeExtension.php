<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Form\Admin;

use Shopsys\FrameworkBundle\Form\Admin\Article\ArticleFormType;
use Shopsys\FrameworkBundle\Form\DatePickerType;
use Shopsys\ShopBundle\Model\Article\Article;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints;

class ArticleFormTypeExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builderArticleDataGroup = $builder->get('articleData');
        $builderArticleDataGroup->add('createdAt', DatePickerType::class, [
            'required' => true,
            'constraints' => [
                new Constraints\NotBlank(['message' => 'Please enter date of creation']),
            ],
            'label' => 'Creation date',
        ]);

        $builderArticleDataGroup->add('placement', ChoiceType::class, [
            'required' => true,
            'choices' => [
                t('in upper menu') => Article::PLACEMENT_TOP_MENU,
                t('in footer') => Article::PLACEMENT_FOOTER,
                t('without positioning') => Article::PLACEMENT_NONE,
                t('o nákupu') => Article::PLACEMENT_SHOPPING,
                t('o nás') => Article::PLACEMENT_ABOUT,
                t('naše služby') => Article::PLACEMENT_SERVICES,
            ],
            'placeholder' => t('-- Choose article position --'),
            'constraints' => [
                new Constraints\NotBlank(['message' => 'Please choose article placement']),
            ],
            'label' => t('Location'),
        ]);

        $builder->add($builderArticleDataGroup);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return ArticleFormType::class;
    }
}
