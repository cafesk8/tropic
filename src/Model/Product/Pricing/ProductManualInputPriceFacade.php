<?php

declare(strict_types=1);

namespace App\Model\Product\Pricing;

use App\Model\Pricing\Group\PricingGroupFacade;
use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductManualInputPriceFacade as BaseProductManualInputPriceFacade;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductManualInputPriceFactoryInterface;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductManualInputPriceRepository;
use Shopsys\FrameworkBundle\Model\Product\Product;

/**
 * @property \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator $em
 * @property \App\Model\Product\Pricing\ProductManualInputPriceRepository $productManualInputPriceRepository
 */
class ProductManualInputPriceFacade extends BaseProductManualInputPriceFacade
{
    /**
     * @var \App\Model\Pricing\Group\PricingGroupFacade
     */
    private PricingGroupFacade $pricingGroupFacade;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \App\Model\Product\Pricing\ProductManualInputPriceRepository $productManualInputPriceRepository
     * @param \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductManualInputPriceFactoryInterface $productManualInputPriceFactory
     * @param \App\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade
     */
    public function __construct(
        EntityManagerInterface $em,
        ProductManualInputPriceRepository $productManualInputPriceRepository,
        ProductManualInputPriceFactoryInterface $productManualInputPriceFactory,
        PricingGroupFacade $pricingGroupFacade
    ) {
        parent::__construct($em, $productManualInputPriceRepository, $productManualInputPriceFactory);
        $this->pricingGroupFacade = $pricingGroupFacade;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param \Shopsys\FrameworkBundle\Component\Money\Money|null $inputPrice
     */
    public function refresh(Product $product, PricingGroup $pricingGroup, ?Money $inputPrice): void
    {
        $defaultPricingGroup = $this->pricingGroupFacade->getDefaultPricingGroup($pricingGroup->getDomainId());
        $defaultInputPrice = $this->productManualInputPriceRepository->findByProductAndPricingGroup($product, $defaultPricingGroup);
        $manualInputPrice = $this->productManualInputPriceRepository->findByProductAndPricingGroup($product, $pricingGroup);

        if ($defaultInputPrice !== null && $defaultInputPrice->getInputPrice() !== null && $defaultPricingGroup !== $pricingGroup) {
            if ($pricingGroup->isCalculatedFromDefault()) {
                $inputPrice = $defaultInputPrice->getInputPrice()->multiply((string)$pricingGroup->getDiscountCoefficient());
            } elseif ($inputPrice !== null) {
                $inputPrice = $inputPrice->multiply((string)$pricingGroup->getDiscountCoefficient());
            }
        }

        if ($manualInputPrice === null) {
            $manualInputPrice = $this->productManualInputPriceFactory->create($product, $pricingGroup, $inputPrice);
            $this->em->persist($manualInputPrice);
            $this->em->flush($manualInputPrice);
        } elseif ($manualInputPrice->getInputPrice() !== null && $inputPrice !== null && $manualInputPrice->getInputPrice()->equals($inputPrice) === false
            || $manualInputPrice->getInputPrice() === null && $inputPrice !== null
            || $manualInputPrice->getInputPrice() !== null && $inputPrice === null
        ) {
            $manualInputPrice->setInputPrice($inputPrice);
            $this->em->flush($manualInputPrice);
        }
    }
}
