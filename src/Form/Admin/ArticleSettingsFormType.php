<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Model\Article\ArticleSettingData;
use Shopsys\FrameworkBundle\Form\GroupType;
use Shopsys\FrameworkBundle\Model\Article\ArticleFacade;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ArticleSettingsFormType extends AbstractType
{
    /**
     * @var \App\Model\Article\ArticleFacade
     */
    private $articleFacade;

    /**
     * @param \App\Model\Article\ArticleFacade $articleFacade
     */
    public function __construct(ArticleFacade $articleFacade)
    {
        $this->articleFacade = $articleFacade;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $articles = $this->articleFacade->getAllByDomainId($options['domain_id']);

        $builderSettingsGroup = $builder->create('settings', GroupType::class, [
            'label' => t('Články věrnostního programu'),
        ]);

        $builderSettingsGroup
            ->add('loyaltyProgramArticle', ChoiceType::class, [
                'required' => false,
                'choices' => $articles,
                'choice_label' => 'name',
                'choice_value' => 'id',
                'placeholder' => t('-- Choose article --'),
                'label' => t('Hlavní'),
                'icon_title' => t('Vyberte článek, který se zobrazí v registraci u checkboxu, zda se chce zákazník stát členem Věrnostního programu.'),
            ]);

        $builderArticleOnHeaderGroup = $this->createArticleOnHeaderPosition($builder, $articles);

        $builder
            ->add($builderSettingsGroup)
            ->add($builderArticleOnHeaderGroup)
            ->add('save', SubmitType::class);
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('domain_id')
            ->setAllowedTypes('domain_id', 'int')
            ->setDefaults([
                'data_class' => ArticleSettingData::class,
                'attr' => ['novalidate' => 'novalidate'],
            ]);
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param \App\Model\Article\Article[] $articles
     * @return \Symfony\Component\Form\FormBuilderInterface
     */
    private function createArticleOnHeaderPosition(FormBuilderInterface $builder, array $articles): FormBuilderInterface
    {
        $builderArticleOnHeaderGroup = $builder->create('article_on_header_setting', GroupType::class, [
            'label' => t('Články v horním menu'),
        ]);

        $builderArticleOnHeaderGroup
            ->add('firstArticleOnHeaderMenu', ChoiceType::class, [
                'required' => false,
                'choices' => $articles,
                'choice_label' => 'name',
                'choice_value' => 'id',
                'placeholder' => t('-- Choose article --'),
                'label' => t('První článek v horním menu'),
                'icon_title' => t('Vyberte článek, který se zobrazí jako první v horním menu hned za články které sou umístený v pozici "horní menu v přehlede článku"'),
            ])
            ->add('secondArticleOnHeaderMenu', ChoiceType::class, [
                'required' => false,
                'choices' => $articles,
                'choice_label' => 'name',
                'choice_value' => 'id',
                'placeholder' => t('-- Choose article --'),
                'label' => t('Druhý článek v horním menu'),
                'icon_title' => t('Vyberte článek, který se zobrazí jako druhý v horním menu hned za články které sou umístený v pozici "horní menu v přehlede článku"'),
            ])
            ->add('thirdArticleOnHeaderMenu', ChoiceType::class, [
                'required' => false,
                'choices' => $articles,
                'choice_label' => 'name',
                'choice_value' => 'id',
                'placeholder' => t('-- Choose article --'),
                'label' => t('Treti článek v horním menu'),
                'icon_title' => t('Vyberte článek, který se zobrazí jako čtvrtý v horním menu hned za Blogem'),
            ]);

        return $builderArticleOnHeaderGroup;
    }
}
