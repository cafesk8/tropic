<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Twig;

use Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Twig_Extension;
use Twig_SimpleFilter;

class AdminPriceExtension extends Twig_Extension
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade
     */
    private $adminDomainTabsFacade;

    /**
     * @var \Shopsys\ShopBundle\Twig\PriceExtension
     */
    private $priceExtension;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade $adminDomainTabsFacade
     * @param \Shopsys\ShopBundle\Twig\PriceExtension $priceExtension
     */
    public function __construct(AdminDomainTabsFacade $adminDomainTabsFacade, PriceExtension $priceExtension)
    {
        $this->adminDomainTabsFacade = $adminDomainTabsFacade;
        $this->priceExtension = $priceExtension;
    }

    /**
     * @inheritDoc
     */
    public function getFilters(): array
    {
        return [
            new Twig_SimpleFilter(
                'priceWithAdminDomainTabDomainCurrency',
                [$this, 'priceWithAdminDomainTabDomainCurrency']
            ),
        ];
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Money\Money $price
     * @return string
     */
    public function priceWithAdminDomainTabDomainCurrency(Money $price): string
    {
        return $this->priceExtension->priceWithCurrencyByDomainIdFilter(
            $price,
            $this->adminDomainTabsFacade->getSelectedDomainId()
        );
    }
}
