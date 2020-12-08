<?php

declare(strict_types=1);

namespace App\Model\Product\Availability;

use App\Model\Product\Product;
use Exception;
use Shopsys\FrameworkBundle\Model\Product\Availability\AvailabilityFacade as BaseAvailabilityFacade;

/**
 * @method \App\Model\Product\Availability\Availability getDefaultInStockAvailability()
 * @property \App\Component\Setting\Setting $setting
 * @method __construct(\Doctrine\ORM\EntityManagerInterface $em, \App\Model\Product\Availability\AvailabilityRepository $availabilityRepository, \App\Component\Setting\Setting $setting, \Shopsys\FrameworkBundle\Model\Product\Availability\ProductAvailabilityRecalculationScheduler $productAvailabilityRecalculationScheduler, \Shopsys\FrameworkBundle\Model\Product\Availability\AvailabilityFactoryInterface $availabilityFactory, \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher)
 * @method \App\Model\Product\Availability\Availability getById(int $availabilityId)
 * @method \App\Model\Product\Availability\Availability create(\App\Model\Product\Availability\AvailabilityData $availabilityData)
 * @method \App\Model\Product\Availability\Availability edit(int $availabilityId, \App\Model\Product\Availability\AvailabilityData $availabilityData)
 * @method setDefaultInStockAvailability(\App\Model\Product\Availability\Availability $availability)
 * @method \App\Model\Product\Availability\Availability[] getAll()
 * @method \App\Model\Product\Availability\Availability[] getAllExceptId(int $availabilityId)
 * @method bool isAvailabilityUsed(\App\Model\Product\Availability\Availability $availability)
 * @method bool isAvailabilityDefault(\App\Model\Product\Availability\Availability $availability)
 * @method dispatchAvailabilityEvent(\App\Model\Product\Availability\Availability $availability, string $eventType)
 * @property \App\Model\Product\Availability\AvailabilityRepository $availabilityRepository
 */
class AvailabilityFacade extends BaseAvailabilityFacade
{
    /**
     * @return string[]
     */
    public function getColorsIndexedByName(): array
    {
        $colors = [];

        foreach ($this->getAll() as $availability) {
            $colors[$availability->getName()] = $availability->getRgbColor();
        }

        return $colors;
    }

    /**
     * @return \App\Model\Product\Availability\Availability
     */
    public function getDefaultOutOfStockAvailability(): Availability
    {
        return $this->getByCode(Availability::OUT_OF_STOCK);
    }

    /**
     * @return \App\Model\Product\Availability\Availability
     */
    public function getSaleStockAvailability(): Availability
    {
        return $this->getByCode(Availability::IN_SALE_STOCK);
    }

    /**
     * @return \App\Model\Product\Availability\Availability
     */
    public function getAvailabilityInDays(): Availability
    {
        return $this->getByCode(Availability::IN_DAYS);
    }

    /**
     * @return \App\Model\Product\Availability\Availability
     */
    public function getExternalOrStoreStockAvailability(): Availability
    {
        return $this->getByCode(Availability::IN_EXTERNAL_STOCK);
    }

    /**
     * @return \App\Model\Product\Availability\Availability
     */
    public function getAvailabilityByVariants(): Availability
    {
        return $this->getByCode(Availability::BY_VARIANTS);
    }

    /**
     * @param string $code
     * @return \App\Model\Product\Availability\Availability
     */
    public function getByCode(string $code): Availability
    {
        return $this->availabilityRepository->getByCode($code);
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param bool $withoutSaleStocks
     * @return \App\Model\Product\Availability\Availability
     */
    public function getAvailability(Product $product, bool $withoutSaleStocks = false): Availability
    {
        if ($product->isSellingDenied()) {
            return $this->getDefaultOutOfStockAvailability();
        }

        if (!$product->isMainVariant() && !$product->isPohodaProductTypeSet()) {
            $saleStocksQuantity = $product->getRealSaleStocksQuantity();
            $internalStockQuantity = $product->getRealInternalStockQuantity();
            $externalStockQuantity = $product->getRealExternalStockQuantity();
            $storeStockQuantity = $product->getRealStoreStockQuantity();

            if ($saleStocksQuantity > 0 && !$withoutSaleStocks) {
                return $this->getSaleStockAvailability();
            } elseif ($internalStockQuantity > 0) {
                return $this->getDefaultInStockAvailability();
            } elseif ($product->isAvailableInDays($withoutSaleStocks)) {
                return $this->getAvailabilityInDays();
            } elseif ($externalStockQuantity > 0) {
                return $this->getExternalOrStoreStockAvailability();
            } elseif ($storeStockQuantity > 0) {
                return $this->getExternalOrStoreStockAvailability();
            }

            return $this->getDefaultOutOfStockAvailability();
        }

        if ($product->isMainVariant()) {
            if ($product->isAnyVariantInStock()) {
                return $this->getAvailabilityByVariants();
            }

            return $this->getDefaultOutOfStockAvailability();
        }

        if ($product->isPohodaProductTypeSet()) {
            $worstAvailability = $this->getDefaultInStockAvailability();

            foreach ($product->getProductSets() as $productSet) {
                $setItemAvailability = $this->getAvailability($productSet->getItem());

                if ($setItemAvailability->getRating() > $worstAvailability->getRating()) {
                    $worstAvailability = $setItemAvailability;
                }
            }

            return $worstAvailability;
        }

        throw new Exception('Unknown availability for product ' . $product->getCatnum());
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param string|null $locale
     * @param bool $inCart
     * @return string
     */
    public function getAvailabilityText(Product $product, ?string $locale = null, bool $inCart = false): string
    {
        $availability = $product->getCalculatedAvailability();

        switch ($availability->getCode()) {
            case Availability::IN_SALE_STOCK:
                $saleStocksQuantity = $product->getRealSaleStocksQuantity();

                return tc(
                    'Za tuto cenu skladem již jen %quantity% %unitName%',
                    $saleStocksQuantity,
                    ['%quantity%' => $saleStocksQuantity, '%unitName%' => $product->getUnit()->getName($locale)],
                    'messages',
                    $locale
                );
            case Availability::IN_STOCK:
                if ($inCart) {
                    return $availability->getName($locale);
                }

                if ($product->isPohodaProductTypeSet()) {
                    $internalStockQuantity = $this->getTheLowestRealInternalStockQuantityFromSetItems($product);
                } else {
                    $internalStockQuantity = $product->getRealInternalStockQuantity();
                }

                return tc(
                    'Ihned k odeslání, další dle dostupnosti',
                    $internalStockQuantity,
                    ['%quantity%' => $internalStockQuantity, '%unitName%' => $product->getUnit()->getName($locale)],
                    'messages',
                    $locale
                );
            case Availability::IN_DAYS:
                $deliveryDays = $product->getDeliveryDays();
                $deliveryDaysNumber = $product->getDeliveryDaysAsNumber();

                if ($product->isPohodaProductTypeSet() && !$product->isAvailableInDays()) {
                    foreach ($product->getProductSets() as $productSet) {
                        if ($productSet->getItem()->getCalculatedAvailability()->isInDays()) {
                            if ($deliveryDaysNumber < $productSet->getItem()->getDeliveryDaysAsNumber()) {
                                $deliveryDays = $productSet->getItem()->getDeliveryDays();
                                $deliveryDaysNumber = $productSet->getItem()->getDeliveryDaysAsNumber();
                            }
                        }
                    }
                }

                return tc(
                    'Dostupnost %days% dní',
                    $deliveryDaysNumber,
                    ['%days%' => $deliveryDays],
                    'messages',
                    $locale
                );
            default:
                return $availability->getName($locale);
        }
    }

    /**
     * @param \App\Model\Product\Product $product
     * @return int
     */
    public function getTheLowestRealInternalStockQuantityFromSetItems(Product $product): int
    {
        $lowestStockQuantity = PHP_INT_MAX;

        foreach ($product->getProductSets() as $productSet) {
            $currentStockQuantity = ($productSet->getItem()->getInternalStockQuantity() + $productSet->getItem()->getRealSaleStocksQuantity()) / $productSet->getItemCount();

            if ($currentStockQuantity < $lowestStockQuantity) {
                $lowestStockQuantity = $currentStockQuantity;
            }
        }

        return $product->getCalculatedStockQuantity((int)$lowestStockQuantity);
    }
}
