<?php

declare(strict_types=1);

namespace App\Form\Admin;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Form\Constraints\NotNegativeMoneyAmount;
use Shopsys\FrameworkBundle\Form\DatePickerType;
use Shopsys\FrameworkBundle\Form\PriceAndVatTableByDomainsType;
use Shopsys\FrameworkBundle\Model\Pricing\Vat\VatFacade;
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
        $actionPricesIndexedByDomainIdBuilder = $builder->create('actionPricesIndexedByDomainId', FormType::class, [
            'compound' => true,
            'render_form_row' => false,
        ]);
        $minOrderPricesIndexedByDomainIdBuilder = $builder->create('minOrderPricesIndexedByDomainId', FormType::class, [
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
                'label' => t('Akční cena'),
            ]);
            $minOrderPricesIndexedByDomainIdBuilder->add($domainId, MoneyType::class, [
                'scale' => 6,
                'required' => false,
                'invalid_message' => 'Please enter price in correct format (positive number with decimal separator)',
                'constraints' => [
                    new NotNegativeMoneyAmount(['message' => 'Price must be greater or equal to zero']),
                ],
                'label' => t('Minimální cena objednávky'),
            ]);
            $actionDatesFromIndexedByDomainIdBuilder->add($domainId, DatePickerType::class, [
                'required' => false,
                'label' => t('Platí od'),
            ]);
            $actionDatesToIndexedByDomainIdBuilder->add($domainId, DatePickerType::class, [
                'required' => false,
                'label' => t('Platí do'),
            ]);
        }

        $builder->add($actionPricesIndexedByDomainIdBuilder);
        $builder->add($minOrderPricesIndexedByDomainIdBuilder);
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
