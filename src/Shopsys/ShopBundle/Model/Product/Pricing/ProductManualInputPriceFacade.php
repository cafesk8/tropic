<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Pricing;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductManualInputPriceFacade as BaseProductManualInputPriceFacade;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductManualInputPriceFactoryInterface;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductManualInputPriceRepository;
use Shopsys\FrameworkBundle\Model\Product\Product;

class ProductManualInputPriceFacade extends BaseProductManualInputPriceFacade
{
    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade
     */
    private $pricingGroupSettingFacade;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductManualInputPriceRepository $productManualInputPriceRepository
     * @param \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductManualInputPriceFactoryInterface $productManualInputPriceFactory
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade $pricingGroupSettingFacade
     */
    public function __construct(EntityManagerInterface $em, ProductManualInputPriceRepository $productManualInputPriceRepository, ProductManualInputPriceFactoryInterface $productManualInputPriceFactory, PricingGroupSettingFacade $pricingGroupSettingFacade)
    {
        parent::__construct($em, $productManualInputPriceRepository, $productManualInputPriceFactory);
        $this->pricingGroupSettingFacade = $pricingGroupSettingFacade;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $product
     * @param \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param \Shopsys\FrameworkBundle\Component\Money\Money|null $inputPrice
     */
    public function refresh(Product $product, PricingGroup $pricingGroup, ?Money $inputPrice)
    {
        $defaultPricingGroup = $this->pricingGroupSettingFacade->getDefaultPricingGroupByDomainId($pricingGroup->getDomainId());
        $defaultInputPrice = $this->productManualInputPriceRepository->findByProductAndPricingGroup($product, $defaultPricingGroup);
        $manualInputPrice = $this->productManualInputPriceRepository->findByProductAndPricingGroup($product, $pricingGroup);

        if ($pricingGroup->getInternalId() !== null && $pricingGroup->getDiscount() !== null) {
            $inputPrice = $defaultInputPrice->getInputPrice()->multiply((string)$pricingGroup->getDiscount());
        }

        if ($manualInputPrice === null) {
            $manualInputPrice = $this->productManualInputPriceFactory->create($product, $pricingGroup, $inputPrice);
            $this->em->persist($manualInputPrice);
        } else {
            $manualInputPrice->setInputPrice($inputPrice);
        }
        $this->em->flush($manualInputPrice);
    }
}
