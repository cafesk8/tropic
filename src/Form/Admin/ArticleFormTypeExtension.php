<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Model\Article\Article;
use Shopsys\FrameworkBundle\Form\Admin\Article\ArticleFormType;
use Shopsys\FrameworkBundle\Form\DatePickerType;
use Shopsys\FrameworkBundle\Form\UrlListType;
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
            'label' => t('Creation date'),
        ]);

        $builderArticleDataGroup->add('placement', ChoiceType::class, [
            'required' => true,
            'choices' => [
                t('in upper menu') => Article::PLACEMENT_TOP_MENU,
                t('without positioning') => Article::PLACEMENT_NONE,
                t('o nákupu') => Article::PLACEMENT_SHOPPING,
                t('naše služby') => Article::PLACEMENT_SERVICES,
            ],
            'placeholder' => t('-- Choose article position --'),
            'constraints' => [
                new Constraints\NotBlank(['message' => 'Please choose article placement']),
            ],
            'label' => t('Location'),
        ]);

        $builder->add($builderArticleDataGroup);

        // see https://github.com/shopsys/shopsys/pull/1787
        $builderSeoDataGroup = $builder->get('seo');
        if ($builderSeoDataGroup->has('urls')) {
            $builderSeoDataGroup
                ->remove('urls')
                ->add('urls', UrlListType::class, [
                    'label' => t('URL addresses'),
                    'route_name' => 'front_article_detail',
                    'entity_id' => $options['article']->getId(),
                ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        yield ArticleFormType::class;
    }
}
