<?php

declare(strict_types=1);

namespace Tests\App\Functional\Model\Pricing;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Component\Setting\Setting;
use Shopsys\FrameworkBundle\Model\Pricing\PricingSetting;
use Shopsys\FrameworkBundle\Model\Pricing\Vat\VatFacade;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductInputPriceRecalculator;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductManualInputPrice;
use Shopsys\FrameworkBundle\Model\Product\ProductData;
use Shopsys\FrameworkBundle\Model\Product\ProductDataFactoryInterface;
use App\DataFixtures\Demo\PricingGroupDataFixture;
use App\DataFixtures\Demo\UnitDataFixture;
use App\Model\Product\Product;
use Tests\FrameworkBundle\Test\IsMoneyEqual;
use Tests\App\Test\TransactionFunctionalTestCase;

class ProductInputPriceRecalculatorTest extends TransactionFunctionalTestCase
{
    public function testRecalculateInputPriceForNewVatPercentWithInputPriceWithoutVat()
    {
        /** @var \Shopsys\FrameworkBundle\Component\Setting\Setting $setting */
        $setting = $this->getContainer()->get(Setting::class);
        /** @var \Shopsys\FrameworkBundle\Model\Pricing\PricingSetting $pricingSetting */
        $pricingSetting = $this->getContainer()->get(PricingSetting::class);
        /** @var \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductInputPriceRecalculator $productInputPriceRecalculator */
        $productInputPriceRecalculator = $this->getContainer()->get(ProductInputPriceRecalculator::class);

        $producDataFactory = $this->getContainer()->get(ProductDataFactoryInterface::class);

        $setting->set(PricingSetting::INPUT_PRICE_TYPE, PricingSetting::INPUT_PRICE_TYPE_WITHOUT_VAT);

        /** @var \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup $pricingGroup */
        $pricingGroup = $this->getReferenceForDomain(PricingGroupDataFixture::PRICING_GROUP_BASIC_DOMAIN, Domain::FIRST_DOMAIN_ID);

        $productData = $producDataFactory->create();
        $productData->unit = $this->getReference(UnitDataFixture::UNIT_PIECES);
        $this->setVats($productData);
        $product = Product::create($productData);

        $productManualInputPrice = new ProductManualInputPrice($product, $pricingGroup, Money::create(1000));
        $inputPriceType = $pricingSetting->getInputPriceType();
        $productInputPriceRecalculator->recalculateInputPriceForNewVatPercent($productManualInputPrice, $inputPriceType, '15');

        $this->assertThat($productManualInputPrice->getInputPrice(), new IsMoneyEqual(Money::create('1052.173913')));
    }

    public function testRecalculateInputPriceForNewVatPercentWithInputPriceWithVat()
    {
        /** @var \Shopsys\FrameworkBundle\Component\Setting\Setting $setting */
        $setting = $this->getContainer()->get(Setting::class);
        /** @var \Shopsys\FrameworkBundle\Model\Pricing\PricingSetting $pricingSetting */
        $pricingSetting = $this->getContainer()->get(PricingSetting::class);
        /** @var \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductInputPriceRecalculator $productInputPriceRecalculator */
        $productInputPriceRecalculator = $this->getContainer()->get(ProductInputPriceRecalculator::class);

        $productDataFactory = $this->getContainer()->get(ProductDataFactoryInterface::class);

        $setting->set(PricingSetting::INPUT_PRICE_TYPE, PricingSetting::INPUT_PRICE_TYPE_WITH_VAT);

        /** @var \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup $pricingGroup */
        $pricingGroup = $this->getReferenceForDomain(PricingGroupDataFixture::PRICING_GROUP_BASIC_DOMAIN, Domain::FIRST_DOMAIN_ID);

        $productData = $productDataFactory->create();
        $productData->unit = $this->getReference(UnitDataFixture::UNIT_PIECES);
        $this->setVats($productData);
        $product = Product::create($productData);

        $productManualInputPrice = new ProductManualInputPrice($product, $pricingGroup, Money::create(1000));

        $inputPriceType = $pricingSetting->getInputPriceType();
        $productInputPriceRecalculator->recalculateInputPriceForNewVatPercent($productManualInputPrice, $inputPriceType, '15');

        $this->assertThat($productManualInputPrice->getInputPrice(), new IsMoneyEqual(Money::create(1000)));
    }

    /**
     * @param \App\Model\Product\ProductData $productData
     */
    private function setVats(ProductData $productData): void
    {
        /** @var \Shopsys\FrameworkBundle\Component\Domain\Domain $domain */
        $domain = $this->getContainer()->get(Domain::class);
        /** @var \Shopsys\FrameworkBundle\Model\Pricing\Vat\VatFacade $vatFacade */
        $vatFacade = $this->getContainer()->get(VatFacade::class);
        $productVatsIndexedByDomainId = [];
        foreach ($domain->getAllIds() as $domainId) {
            $productVatsIndexedByDomainId[$domainId] = $vatFacade->getDefaultVatForDomain($domainId);
        }
        $productData->vatsIndexedByDomainId = $productVatsIndexedByDomainId;
    }
}
