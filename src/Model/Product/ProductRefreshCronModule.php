<?php

declare(strict_types=1);

namespace App\Model\Product;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\Plugin\Cron\SimpleCronModuleInterface;
use Symfony\Bridge\Monolog\Logger;

class ProductRefreshCronModule implements SimpleCronModuleInterface
{
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
     */
    public function __construct(
        ProductFacade $productFacade,
        ProductDataFactory $productDataFactory,
        EntityManagerInterface $entityManager
    ) {
        $this->productFacade = $productFacade;
        $this->productDataFactory = $productDataFactory;
        $this->entityManager = $entityManager;
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
            $product->markForExport();
            $this->entityManager->flush();
            $this->logger->addInfo('Product refreshed and scheduled for export to Elastic', ['id' => $productId]);
        }
    }
}
