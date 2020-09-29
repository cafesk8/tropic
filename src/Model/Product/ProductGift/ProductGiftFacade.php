<?php

declare(strict_types=1);

namespace App\Model\Product\ProductGift;

use App\Model\Product\Product;
use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Model\Product\Elasticsearch\ProductExportScheduler;

class ProductGiftFacade
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;

    /**
     * @var \App\Model\Product\ProductGift\ProductGiftRepository
     */
    private $productGiftRepository;

    /**
     * @var \App\Model\Product\ProductGift\ProductGiftFactory
     */
    private $productGiftFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Elasticsearch\ProductExportScheduler
     */
    private $productExportScheduler;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \App\Model\Product\ProductGift\ProductGiftRepository $productGiftRepository
     * @param \App\Model\Product\ProductGift\ProductGiftFactory $productGiftFactory
     * @param \Shopsys\FrameworkBundle\Model\Product\Elasticsearch\ProductExportScheduler $productExportScheduler
     */
    public function __construct(
        EntityManagerInterface $em,
        ProductGiftRepository $productGiftRepository,
        ProductGiftFactory $productGiftFactory,
        ProductExportScheduler $productExportScheduler
    ) {
        $this->em = $em;
        $this->productGiftRepository = $productGiftRepository;
        $this->productGiftFactory = $productGiftFactory;
        $this->productExportScheduler = $productExportScheduler;
    }

    /**
     * @param int $productGiftId
     * @return \App\Model\Product\ProductGift\ProductGift
     */
    public function getById(int $productGiftId): ProductGift
    {
        return $this->productGiftRepository->getById($productGiftId);
    }

    /**
     * @param \App\Model\Product\ProductGift\ProductGiftData $productGiftData
     * @return \App\Model\Product\ProductGift\ProductGift
     */
    public function create(ProductGiftData $productGiftData): ProductGift
    {
        $productGift = $this->productGiftFactory->create($productGiftData);

        $this->em->persist($productGift);
        $this->em->flush();

        foreach ($this->getProductIdsForExport($productGift) as $productId) {
            $this->productExportScheduler->scheduleRowIdForImmediateExport($productId);
        }

        return $productGift;
    }

    /**
     * @param \App\Model\Product\ProductGift\ProductGift $productGift
     * @param \App\Model\Product\ProductGift\ProductGiftData $productGiftData
     * @return \App\Model\Product\ProductGift\ProductGift
     */
    public function edit(ProductGift $productGift, ProductGiftData $productGiftData): ProductGift
    {
        $exportIds = $this->getProductIdsForExport($productGift);
        $productGift->edit($productGiftData);

        $this->em->flush();

        $exportIds = array_unique(array_merge($exportIds, $this->getProductIdsForExport($productGift)));

        foreach ($exportIds as $productId) {
            $this->productExportScheduler->scheduleRowIdForImmediateExport($productId);
        }

        return $productGift;
    }

    /**
     * @param \App\Model\Product\ProductGift\ProductGift $productGift
     */
    public function delete(ProductGift $productGift): void
    {
        $exportIds = $this->getProductIdsForExport($productGift);

        $this->em->remove($productGift);
        $this->em->flush();

        foreach ($exportIds as $productId) {
            $this->productExportScheduler->scheduleRowIdForImmediateExport($productId);
        }
    }

    /**
     * @param \App\Model\Product\ProductGift\ProductGift $productGift
     * @return int[]
     */
    private function getProductIdsForExport(ProductGift $productGift): array
    {
        return array_map(function (Product $product) {
            return $product->getId();
        }, $productGift->getProducts());
    }

    /**
     * @param \App\Model\Product\Product $gift
     * @return \App\Model\Product\ProductGift\ProductGift[]
     */
    public function getByGift(Product $gift): array
    {
        return $this->productGiftRepository->getByGift($gift);
    }
}
