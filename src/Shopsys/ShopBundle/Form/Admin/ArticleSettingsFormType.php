<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Form\Admin;

use Shopsys\FrameworkBundle\Form\GroupType;
use Shopsys\FrameworkBundle\Model\Article\ArticleFacade;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ArticleSettingsFormType extends AbstractType
{
    public const FIELD_BUSHMAN_ARTICLE = 'bushmanClubArticle';
    public const FIELD_OUR_STORY_ARTICLE = 'ourStoryArticle';
    public const FIELD_OUR_VALUES_ARTICLE = 'ourValuesArticle';
    public const FIELD_FIRST_HEADER_ARTICLE = 'firstHeaderArticle';
    public const FIELD_SECOND_HEADER_ARTICLE = 'secondHeaderArticle';

    /**
     * @var \Shopsys\FrameworkBundle\Model\Article\ArticleFacade
     */
    private $articleFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Article\ArticleFacade $articleFacade
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
            'label' => t('Settings'),
        ]);

        $builderSettingsGroup
            ->add(self::FIELD_BUSHMAN_ARTICLE, ChoiceType::class, [
                'required' => false,
                'choices' => $articles,
                'choice_label' => 'name',
                'choice_value' => 'id',
                'placeholder' => t('-- Choose article --'),
                'label' => t('(Banner) Bushman Club'),
                'icon_title' => t('Vyberte článek, který se zobrazí v registraci u checkboxu, zda se chce stát zákazník členem Bushman Clubu, a také jako odkaz pro banner Bushman club'),
            ])
            ->add(self::FIELD_OUR_STORY_ARTICLE, ChoiceType::class, [
                'required' => false,
                'choices' => $articles,
                'choice_label' => 'name',
                'choice_value' => 'id',
                'placeholder' => t('-- Choose article --'),
                'label' => t('Banner Náš příběh'),
                'icon_title' => t('Vyberte článek, který se zobrazí jako odkaz u banneru Náš příběh'),
            ])
            ->add(self::FIELD_OUR_VALUES_ARTICLE, ChoiceType::class, [
                'required' => false,
                'choices' => $articles,
                'choice_label' => 'name',
                'choice_value' => 'id',
                'placeholder' => t('-- Choose article --'),
                'label' => t('Banner Naše hodnoty'),
                'icon_title' => t('Vyberte článek, který se zobrazí jako odkaz u banneru Naše hodnoty'),
            ])
            ->add(self::FIELD_FIRST_HEADER_ARTICLE, ChoiceType::class, [
                'required' => false,
                'choices' => $articles,
                'choice_label' => 'name',
                'choice_value' => 'id',
                'placeholder' => t('-- Choose article --'),
                'label' => t('První článek v horním menu'),
                'icon_title' => t('Vyberte článek, který se zobrazí jako první v horním menu hned za články které sou umístený v pozici "horní menu v přehlede článku"'),
            ])
            ->add(self::FIELD_SECOND_HEADER_ARTICLE, ChoiceType::class, [
                'required' => false,
                'choices' => $articles,
                'choice_label' => 'name',
                'choice_value' => 'id',
                'placeholder' => t('-- Choose article --'),
                'label' => t('Druhý článek v horním menu'),
                'icon_title' => t('Vyberte článek, který se zobrazí jako druhý v horním menu hned za články které sou umístený v pozici "horní menu v přehlede článku"'),
            ]);

        $builder
            ->add($builderSettingsGroup)
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
                'attr' => ['novalidate' => 'novalidate'],
            ]);
    }
}
