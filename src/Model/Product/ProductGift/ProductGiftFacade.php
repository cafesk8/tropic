<?php

declare(strict_types=1);

namespace App\Model\Product\ProductGift;

use Doctrine\ORM\EntityManagerInterface;

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
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \App\Model\Product\ProductGift\ProductGiftRepository $productGiftRepository
     * @param \App\Model\Product\ProductGift\ProductGiftFactory $productGiftFactory
     */
    public function __construct(
        EntityManagerInterface $em,
        ProductGiftRepository $productGiftRepository,
        ProductGiftFactory $productGiftFactory
    ) {
        $this->em = $em;
        $this->productGiftRepository = $productGiftRepository;
        $this->productGiftFactory = $productGiftFactory;
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

        return $productGift;
    }

    /**
     * @param \App\Model\Product\ProductGift\ProductGift $productGift
     * @param \App\Model\Product\ProductGift\ProductGiftData $productGiftData
     * @return \App\Model\Product\ProductGift\ProductGift
     */
    public function edit(ProductGift $productGift, ProductGiftData $productGiftData): ProductGift
    {
        $productGift->edit($productGiftData);

        $this->em->flush();

        return $productGift;
    }

    /**
     * @param int $productGiftId
     */
    public function delete(int $productGiftId): void
    {
        $productGift = $this->productGiftRepository->getById($productGiftId);

        $this->em->remove($productGift);
        $this->em->flush();
    }
}
