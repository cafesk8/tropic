<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Mall;

use MPAPI\Exceptions\ApplicationException;
use MPAPI\Services\Products;
use Shopsys\Plugin\Cron\IteratedCronModuleInterface;
use Shopsys\ShopBundle\Component\Mall\MallClient;
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
     * @var \Shopsys\ShopBundle\Model\Product\Mall\ProductMallExportMapper
     */
    private $productMallExportMapper;

    /**
     * @var \Shopsys\ShopBundle\Component\Mall\MallClient
     */
    private $mallClient;

    /**
     * @param \Shopsys\ShopBundle\Model\Product\ProductFacade $productFacade
     */
    public function __construct(ProductFacade $productFacade, ProductMallExportMapper $productMallExportMapper, MallClient $mallClient)
    {
        $this->productFacade = $productFacade;
        $this->productMallExportMapper = $productMallExportMapper;
        $this->mallClient = $mallClient;
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

        try {
            $this->processItems($productsToExport);

            if (count($productsToExport) < self::BATCH_SIZE) {
                $this->logger->info('All products are exported to Mall.cz.');
                return false;
            } else {
                $this->logger->info('Batch is exported.');
                return true;
            }
        } catch (ApplicationException $exception) {
            $this->logger->addError(sprintf('Products were not exported to Mall.cz due to exception: %s', $exception->getMessage()), [
                'exception' => $exception,
            ]);
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
        $productSynchronizer = new Products($this->mallClient->getClient());
        $productIds = [];

        foreach ($productsToExport as $product) {
            d($this->productMallExportMapper->mapProductOrMainVariantGroup($product));
            $this->logger->addInfo(sprintf('Product with ID `%s` is prepared for export to Mall.cz', $product->getId()));
            $productIds[] = $product->getId();
        }

        //$productSynchronizer->post();
        //$this->productFacade->markProductsAsExportedToMall($productsToExport);
        //$this->logger->addInfo(sprintf('Products with ID `%s` were exported to Mall.cz', implode(',', $productIds)));
    }
}
