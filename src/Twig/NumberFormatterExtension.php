<?php

declare(strict_types=1);

namespace App\Twig;

use CommerceGuys\Intl\Currency\CurrencyRepositoryInterface;
use CommerceGuys\Intl\NumberFormat\NumberFormatRepositoryInterface;
use Shopsys\FrameworkBundle\Model\Administration\AdministrationFacade;
use Shopsys\FrameworkBundle\Model\Localization\Localization;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFacade;
use Shopsys\FrameworkBundle\Twig\NumberFormatterExtension as BaseNumberFormatterExtension;

class NumberFormatterExtension extends BaseNumberFormatterExtension
{
    /**
     * @var \App\Model\Pricing\Currency\CurrencyFacade
     */
    private $currencyFacade;

    /**
     * @var \CommerceGuys\Intl\Currency\CurrencyRepositoryInterface
     */
    private $intlCurrencyRepository;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Localization\Localization $localization
     * @param \CommerceGuys\Intl\NumberFormat\NumberFormatRepositoryInterface $numberFormatRepository
     * @param \App\Model\Pricing\Currency\CurrencyFacade $currencyFacade
     * @param \CommerceGuys\Intl\Currency\CurrencyRepositoryInterface $intlCurrencyRepository
     * @param \Shopsys\FrameworkBundle\Model\Administration\AdministrationFacade $administrationFacade
     */
    public function __construct(
        Localization $localization,
        NumberFormatRepositoryInterface $numberFormatRepository,
        CurrencyFacade $currencyFacade,
        CurrencyRepositoryInterface $intlCurrencyRepository,
        AdministrationFacade $administrationFacade
    ) {
        parent::__construct($localization, $numberFormatRepository, $administrationFacade);

        $this->currencyFacade = $currencyFacade;
        $this->intlCurrencyRepository = $intlCurrencyRepository;
    }

    /**
     * What is it?
     *
     * This method is copied from PriceExtension, but method addOrderItemDiscount in OrderFacade needs info
     * about currency and it is simpler copy this method than override any classes from framework
     *
     * @param int $currencyId
     * @param string $locale
     * @return string
     */
    public function getCurrencySymbolByCurrencyIdAndLocale(int $currencyId, string $locale): string
    {
        $currency = $this->currencyFacade->getById($currencyId);
        $intlCurrency = $this->intlCurrencyRepository->get($currency->getCode(), $locale);

        return $intlCurrency->getSymbol();
    }
}
