<?php

declare(strict_types=1);

namespace App\Model\Product;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Model\Product\Elasticsearch\ProductExportSubscriber;
use Shopsys\Plugin\Cron\SimpleCronModuleInterface;
use Symfony\Bridge\Monolog\Logger;

class ProductRefreshCronModule implements SimpleCronModuleInterface
{
    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Elasticsearch\ProductExportSubscriber
     */
    private $productExportSubscriber;

    /**
     * @var \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator
     */
    private $entityManager;

    /**
     * @var \App\Model\Product\ProductDataFactory
     */
    private $productDataFactory;

    /**
     * @var \App\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @var \Monolog\Logger
     */
    private $logger;

    /**
     * @param \App\Model\Product\ProductFacade $productFacade
     * @param \App\Model\Product\ProductDataFactory $productDataFactory
     * @param \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator $entityManager
     * @param \Shopsys\FrameworkBundle\Model\Product\Elasticsearch\ProductExportSubscriber $productExportSubscriber
     */
    public function __construct(
        ProductFacade $productFacade,
        ProductDataFactory $productDataFactory,
        EntityManagerInterface $entityManager,
        ProductExportSubscriber $productExportSubscriber
    ) {
        $this->productFacade = $productFacade;
        $this->productDataFactory = $productDataFactory;
        $this->entityManager = $entityManager;
        $this->productExportSubscriber = $productExportSubscriber;
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
        $products = $this->productFacade->getProductsForRefresh();
        if (empty($products)) {
            $this->logger->addInfo('No products to refresh');
            return;
        }

        foreach ($products as $product) {
            $productData = $this->productDataFactory->createFromProduct($product);
            $productId = $product->getId();
            $this->productFacade->edit($productId, $productData);
            $this->entityManager->flush();
            $this->logger->addInfo('Product refreshed', ['id' => $productId]);
        }

        $this->productExportSubscriber->exportScheduledRows();
    }
}
