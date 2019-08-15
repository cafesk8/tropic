<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Mall;

use MPAPI\Exceptions\ApplicationException;
use Shopsys\Plugin\Cron\IteratedCronModuleInterface;
use Shopsys\ShopBundle\Component\Mall\MallFacade;
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
     * @var \Shopsys\ShopBundle\Component\Mall\MallFacade
     */
    private $mallFacade;

    /**
     * @param \Shopsys\ShopBundle\Model\Product\ProductFacade $productFacade
     * @param \Shopsys\ShopBundle\Model\Product\Mall\ProductMallExportMapper $productMallExportMapper
     * @param \Shopsys\ShopBundle\Component\Mall\MallFacade $mallFacade
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
        $exportedProducts = [];

        foreach ($productsToExport as $product) {
            $preparedProductToExport = $this->productMallExportMapper->mapProductOrMainVariantGroup($product);
            $response = $this->mallFacade->createOrUpdateProduct($preparedProductToExport);

            if ($response === true) {
                $this->logger->addInfo(sprintf('Product with ID `%s` was exported to Mall.cz', $product->getId()));
                $exportedProducts[] = $product;
            }
        }

        $this->productFacade->markProductsAsExportedToMall($exportedProducts);
    }
}
