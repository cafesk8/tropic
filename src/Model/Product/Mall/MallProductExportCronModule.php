<?php

declare(strict_types=1);

namespace App\Model\Product\Mall;

use App\Component\Mall\MallFacade;
use App\Model\Product\Mall\Exception\InvalidProductForMallExportException;
use App\Model\Product\ProductFacade;
use Shopsys\Plugin\Cron\IteratedCronModuleInterface;
use Symfony\Bridge\Monolog\Logger;

class MallProductExportCronModule implements IteratedCronModuleInterface
{
    private const BATCH_SIZE = 100;

    /**
     * @var \Symfony\Bridge\Monolog\Logger
     */
    protected $logger;

    /**
     * @var \App\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @var \App\Model\Product\Mall\ProductMallExportMapper
     */
    private $productMallExportMapper;

    /**
     * @var \App\Component\Mall\MallFacade
     */
    private $mallFacade;

    /**
     * @param \App\Model\Product\ProductFacade $productFacade
     * @param \App\Model\Product\Mall\ProductMallExportMapper $productMallExportMapper
     * @param \App\Component\Mall\MallFacade $mallFacade
     */
    public function __construct(ProductFacade $productFacade, ProductMallExportMapper $productMallExportMapper, MallFacade $mallFacade)
    {
        $this->productFacade = $productFacade;
        $this->productMallExportMapper = $productMallExportMapper;
        $this->mallFacade = $mallFacade;
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
     * @param \App\Model\Product\Product[] $productsToExport
     */
    private function processItems(array $productsToExport): void
    {
        $exportedProducts = [];

        foreach ($productsToExport as $product) {
            try {
                $preparedProductToExport = $this->productMallExportMapper->mapProduct($product);

                $isCreatedOrUpdated = $this->mallFacade->createOrUpdateProduct($preparedProductToExport);

                if ($isCreatedOrUpdated === true) {
                    $this->logger->addInfo(sprintf('Product with ID `%s` was exported to Mall.cz', $product->getId()));
                    $exportedProducts[] = $product;
                }
            } catch (InvalidProductForMallExportException $ex) {
                $this->logger->addInfo(sprintf('Product with ID `%s` was exported to Mall.cz. Error: `%s`', $product->getId(), $ex->getMessage()));
                $exportedProducts[] = $product;

                // TODO We can save this errors into some db tables and we can show errors in some part of admin
            }
        }

        $this->productFacade->markProductsAsExportedToMall($exportedProducts);
    }
}
