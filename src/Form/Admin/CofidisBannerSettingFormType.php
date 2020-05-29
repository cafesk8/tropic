<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Model\Pricing\Currency\CurrencyFacade;
use Shopsys\FrameworkBundle\Form\GroupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Constraints\Type;

class CofidisBannerSettingFormType extends AbstractType
{
    /**
     * @var \App\Model\Pricing\Currency\CurrencyFacade
     */
    private $currencyFacade;

    /**
     * @param \App\Model\Pricing\Currency\CurrencyFacade $currencyFacade
     */
    public function __construct(CurrencyFacade $currencyFacade)
    {
        $this->currencyFacade = $currencyFacade;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $domainId = $options['domainId'];

        $deadlineGroup = $builder
            ->create('cofidisBanner', GroupType::class, [
                'label' => t('Nastavení banneru Cofidis'),
            ]);
        $deadlineGroup
            ->add('minimumPrice', MoneyType::class, [
                'required' => true,
                'label' => t('Zobrazit kalukačku od'),
                'constraints' => [
                    new Constraints\NotBlank(['message' => 'Vyplňte prosím minimální částku pro zobrazení kalkulačky na detailu produktu.']),
                    new Type(['type' => 'numeric', 'message' => 'Vložte prosím číslo.']),
                ],
                'attr' => [
                    'icon' => true,
                    'iconTitle' => t('Kallkulačka se bude od této cenové hladiny zobrazovat na detailu produktu.'),
                ],
                'currency' => $this->currencyFacade->getDomainDefaultCurrencyByDomainId($domainId)->getCode(),
            ]);

        $builder->add($deadlineGroup);
        $builder->add('save', SubmitType::class);
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('domainId')
            ->setDefaults([
            'attr' => ['novalidate' => 'novalidate'],
        ]);
    }
}
