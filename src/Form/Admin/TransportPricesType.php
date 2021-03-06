<?php

declare(strict_types=1);

namespace App\Form\Admin;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Form\Constraints\NotNegativeMoneyAmount;
use Shopsys\FrameworkBundle\Form\DatePickerType;
use Shopsys\FrameworkBundle\Form\PriceAndVatTableByDomainsType;
use Shopsys\FrameworkBundle\Model\Pricing\Vat\VatFacade;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;

class TransportPricesType extends PriceAndVatTableByDomainsType
{
    private Domain $domain;

    /**
     * @inheritDoc
     */
    public function __construct(Domain $domain, VatFacade $vatFacade)
    {
        parent::__construct($domain, $vatFacade);
        $this->domain = $domain;
    }

    /**
     * @inheritDoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
        $minFreeOrderPricesIndexedByDomainIdBuilder = $builder->create('minFreeOrderPricesIndexedByDomainId', FormType::class, [
            'compound' => true,
            'render_form_row' => false,
        ]);
        $maxOrderPricesLimitIndexedByDomainIdBuilder = $builder->create('maxOrderPricesLimitIndexedByDomainId', FormType::class, [
            'compound' => true,
            'render_form_row' => false,
        ]);
        $actionActiveIndexedByDomainIdBuilder = $builder->create('actionActiveIndexedByDomainId', FormType::class, [
            'compound' => true,
            'render_form_row' => false,
        ]);

        foreach ($this->domain->getAllIds() as $domainId) {
            $minFreeOrderPricesIndexedByDomainIdBuilder->add($domainId, MoneyType::class, [
                'scale' => 6,
                'required' => false,
                'invalid_message' => 'Please enter price in correct format (positive number with decimal separator)',
                'constraints' => [
                    new NotNegativeMoneyAmount(['message' => 'Price must be greater or equal to zero']),
                ],
                'label' => t('Minim??ln?? cena objedn??vky pro dopravu zdarma'),
            ]);
            $maxOrderPricesLimitIndexedByDomainIdBuilder->add($domainId, MoneyType::class, [
                'scale' => 6,
                'required' => false,
                'invalid_message' => 'Please enter price in correct format (positive number with decimal separator)',
                'constraints' => [
                    new NotNegativeMoneyAmount(['message' => 'Price must be greater or equal to zero']),
                ],
                'label' => t('Maxim??ln?? hodnota objedn??vky'),
            ]);
            $actionActiveIndexedByDomainIdBuilder->add($domainId, CheckboxType::class, [
                'required' => false,
                'label' => t('Akce na dopravu'),
            ]);
        }

        $builder->add($minFreeOrderPricesIndexedByDomainIdBuilder);
        $builder->add($maxOrderPricesLimitIndexedByDomainIdBuilder);
        $builder->add($actionActiveIndexedByDomainIdBuilder);
        $this->addActionPrices($builder);
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     */
    private function addActionPrices(FormBuilderInterface $builder): void
    {
        $actionPricesIndexedByDomainIdBuilder = $builder->create('actionPricesIndexedByDomainId', FormType::class, [
            'compound' => true,
            'render_form_row' => false,
        ]);
        $minActionOrderPricesIndexedByDomainIdBuilder = $builder->create('minActionOrderPricesIndexedByDomainId', FormType::class, [
            'compound' => true,
            'render_form_row' => false,
        ]);
        $actionDatesFromIndexedByDomainIdBuilder = $builder->create('actionDatesFromIndexedByDomainId', FormType::class, [
            'compound' => true,
            'render_form_row' => false,
        ]);
        $actionDatesToIndexedByDomainIdBuilder = $builder->create('actionDatesToIndexedByDomainId', FormType::class, [
            'compound' => true,
            'render_form_row' => false,
        ]);

        foreach ($this->domain->getAllIds() as $domainId) {
            $actionPricesIndexedByDomainIdBuilder->add($domainId, MoneyType::class, [
                'scale' => 6,
                'required' => false,
                'invalid_message' => 'Please enter price in correct format (positive number with decimal separator)',
                'constraints' => [
                    new NotNegativeMoneyAmount(['message' => 'Price must be greater or equal to zero']),
                ],
                'label' => t('Ak??n?? cena'),
            ]);
            $minActionOrderPricesIndexedByDomainIdBuilder->add($domainId, MoneyType::class, [
                'scale' => 6,
                'required' => false,
                'invalid_message' => 'Please enter price in correct format (positive number with decimal separator)',
                'constraints' => [
                    new NotNegativeMoneyAmount(['message' => 'Price must be greater or equal to zero']),
                ],
                'label' => t('Minim??ln?? cena objedn??vky pro akci na dopravu'),
            ]);
            $actionDatesFromIndexedByDomainIdBuilder->add($domainId, DatePickerType::class, [
                'required' => false,
                'label' => t('Plat?? od'),
            ]);
            $actionDatesToIndexedByDomainIdBuilder->add($domainId, DatePickerType::class, [
                'required' => false,
                'label' => t('Plat?? do'),
            ]);
        }

        $builder->add($actionPricesIndexedByDomainIdBuilder);
        $builder->add($minActionOrderPricesIndexedByDomainIdBuilder);
        $builder->add($actionDatesFromIndexedByDomainIdBuilder);
        $builder->add($actionDatesToIndexedByDomainIdBuilder);
    }

    /**
     * @inheritDoc
     */
    public function getParent(): string
    {
        return PriceAndVatTableByDomainsType::class;
    }
}
