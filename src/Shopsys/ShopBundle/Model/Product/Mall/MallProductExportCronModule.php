<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Mall;

use Shopsys\Plugin\Cron\IteratedCronModuleInterface;
use Shopsys\ShopBundle\Model\Product\ProductFacade;
use Symfony\Bridge\Monolog\Logger;

class MallProductExportCronModule implements IteratedCronModuleInterface
{
    private const BATCH_SIZE = 100;

    /**
     * @var \Symfony\Bridge\Monolog\Logger
     */
    protected $logger;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @param \Shopsys\ShopBundle\Model\Product\ProductFacade $productFacade
     */
    public function __construct(ProductFacade $productFacade)
    {
        $this->productFacade = $productFacade;
    }

    /**
     * @param \Symfony\Bridge\Monolog\Logger $logger
     */
    public function setLogger(Logger $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @return bool|void
     */
    public function iterate()
    {
        $productsToExport = $this->productFacade->getProductsForExportToMall(self::BATCH_SIZE);
        $this->processItems($productsToExport);

        if (count($productsToExport) < self::BATCH_SIZE) {
            $this->logger->info('All products are exported to Mall.cz.');
            return false;
        } else {
            $this->logger->info('Batch is exported.');
            return true;
        }
    }

    public function sleep(): void
    {
    }

    public function wakeUp(): void
    {
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product[] $productsToExport
     */
    private function processItems(array $productsToExport): void
    {
        foreach ($productsToExport as $product) {
            $this->productFacade->markProductAsExportedToMall($product);
            $this->logger->addInfo(sprintf('Product with ID `%s` was exported to Mall.cz', $product->getId()));
        }
    }
}
