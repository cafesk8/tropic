<?php

declare(strict_types=1);

namespace App\Model\Product\Flag;

use App\Model\Product\ProductDataFactory;
use App\Model\Product\ProductFacade;
use Exception;
use Shopsys\Plugin\Cron\SimpleCronModuleInterface;
use Symfony\Bridge\Monolog\Logger;

class SpecialFlagsRefreshCronModule implements SimpleCronModuleInterface
{
    private Logger $logger;

    private ProductDataFactory $productDataFactory;

    private ProductFacade $productFacade;

    private ProductFlagFacade $productFlagFacade;

    /**
     * @param \App\Model\Product\ProductFacade $productFacade
     * @param \App\Model\Product\ProductDataFactory $productDataFactory
     * @param \App\Model\Product\Flag\ProductFlagFacade $productFlagFacade
     */
    public function __construct(
        ProductFacade $productFacade,
        ProductDataFactory $productDataFactory,
        ProductFlagFacade $productFlagFacade
    ) {
        $this->productDataFactory = $productDataFactory;
        $this->productFacade = $productFacade;
        $this->productFlagFacade = $productFlagFacade;
    }

    /**
     * @param \Symfony\Bridge\Monolog\Logger $logger
     */
    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function run()
    {
        $productFlags = $this->productFlagFacade->getStartingOrEndingAroundCurrentDate();

        foreach ($productFlags as $productFlag) {
            $product = $productFlag->getProduct();

            try {
                $productData = $this->productDataFactory->createFromProduct($product);
                $this->productFacade->edit($product->getId(), $productData);
                $this->logger->addInfo('Product refreshed', ['id' => $product->getId()]);
            } catch (Exception $exception) {
                $this->logger->addError('Product refresh failed', ['id' => $product->getId(), 'reason' => $exception->getMessage()]);
            }
        }
    }
}
