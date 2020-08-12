<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Component\Domain\DomainHelper;
use App\Model\Pricing\Currency\CurrencyFacade;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class CofidisBannerSettingFormType extends AbstractType
{
    private CurrencyFacade $currencyFacade;

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
        $builder
            ->add('minimumPrice', MoneyType::class, [
                'required' => true,
                'label' => t('Zobrazit kalkulačku na CZ doméně od'),
                'constraints' => [
                    new Constraints\NotBlank(['message' => 'Vyplňte prosím minimální částku pro zobrazení kalkulačky na detailu produktu.']),
                ],
                'attr' => [
                    'icon' => true,
                    'iconTitle' => t('Kalkulačka se bude od této cenové hladiny zobrazovat na detailu produktu.'),
                ],
                'currency' => $this->currencyFacade->getDomainDefaultCurrencyByDomainId(DomainHelper::CZECH_DOMAIN)->getCode(),
            ]);

        $builder->add('save', SubmitType::class);
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => ['novalidate' => 'novalidate'],
        ]);
    }
}
