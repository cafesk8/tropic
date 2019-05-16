<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Transfer;

use Shopsys\FrameworkBundle\Model\Product\Availability\AvailabilityFacade;
use Shopsys\ShopBundle\Component\Domain\DomainHelper;
use Shopsys\ShopBundle\Model\Product\ProductData;
use Shopsys\ShopBundle\Model\Product\ProductDataFactory;

class ProductTransferMapper
{
    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductDataFactory
     */
    private $productDataFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Availability\AvailabilityFacade
     */
    private $availabilityFacade;

    /**
     * @param \Shopsys\ShopBundle\Model\Product\ProductDataFactory $productDataFactory
     * @param \Shopsys\FrameworkBundle\Model\Product\Availability\AvailabilityFacade $availabilityFacade
     */
    public function __construct(ProductDataFactory $productDataFactory, AvailabilityFacade $availabilityFacade)
    {
        $this->productDataFactory = $productDataFactory;
        $this->availabilityFacade = $availabilityFacade;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Transfer\ProductTransferResponseItemData $productTransferResponseItemData
     * @return \Shopsys\ShopBundle\Model\Product\ProductData
     */
    public function mapTransferDataToProductData(
        ProductTransferResponseItemData $productTransferResponseItemData
    ): ProductData {
        $productData = $this->productDataFactory->create();

        $productData->transferNumber = $productTransferResponseItemData->getNumber();
        $productData->name['cs'] = $productTransferResponseItemData->getName();
        $productData->descriptions[DomainHelper::CZECH_DOMAIN] = $productTransferResponseItemData->getDescription();
        $productData->availability = $this->availabilityFacade->getDefaultInStockAvailability();

        return $productData;
    }
}
