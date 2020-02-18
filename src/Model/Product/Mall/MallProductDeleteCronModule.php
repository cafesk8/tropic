<?php

declare(strict_types=1);

namespace App\Model\Product\Mall;

use App\Component\Domain\DomainHelper;
use App\Component\Mall\MallFacade;
use App\Model\Product\Product;
use App\Model\Product\ProductFacade;
use Shopsys\Plugin\Cron\SimpleCronModuleInterface;
use Symfony\Bridge\Monolog\Logger;

class MallProductDeleteCronModule implements SimpleCronModuleInterface
{
    /**
     * @var \Symfony\Bridge\Monolog\Logger
     */
    protected $logger;

    /**
     * @var \App\Component\Mall\MallFacade
     */
    private $mallFacade;

    /**
     * @var \App\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @param \App\Component\Mall\MallFacade $mallFacade
     * @param \App\Model\Product\ProductFacade $productFacade
     */
    public function __construct(MallFacade $mallFacade, ProductFacade $productFacade)
    {
        $this->mallFacade = $mallFacade;
        $this->productFacade = $productFacade;
    }

    /**
     * @param \Symfony\Bridge\Monolog\Logger $logger
     */
    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * This method is called to run the CRON module.
     */
    public function run()
    {
        $productsToDelete = $this->productFacade->getProductsToDeleteFromMall();

        foreach ($productsToDelete as $product) {
            $isDeleted = $this->deleteProductOrVariant($product);

            if ($isDeleted) {
                $this->logger->addInfo(sprintf('%s with ID `%s` was deleted from Mall.cz', $product->isVariant() ? 'Variant' : 'Product', $product->getId()));
            }
        }
    }

    /**
     * @param \App\Model\Product\Product $variant
     * @return bool
     */
    private function deleteVariant(Product $variant): bool
    {
        $visibleVariantsCount = $this->productFacade->getCountOfVisibleVariantsForMainVariant($variant->getMainVariant(), DomainHelper::CZECH_DOMAIN);

        if ($visibleVariantsCount <= 1) {
            return $this->mallFacade->deleteProduct($variant->getMainVariant()->getId());
        } else {
            return $this->mallFacade->deleteVariant($variant->getMainVariant()->getId(), $variant->getId());
        }
    }

    /**
     * @param \App\Model\Product\Product $product
     * @return bool
     */
    private function deleteProductOrVariant(Product $product): bool
    {
        if ($product->isMainVariant() === false && $product->isVariant() === false) {
            return $this->mallFacade->deleteProduct($product->getId());
        } elseif ($product->isVariant()) {
            return $this->deleteVariant($product);
        } elseif ($product->isMainVariant() === true && $product->getMainVariantGroup() === null) {
            return $this->mallFacade->deleteProduct($product->getId());
        } elseif ($product->isMainVariant() === true && $product->getMainVariantGroup() !== null) {
            return $this->deleteVariant($product);
        }

        return false;
    }
}
