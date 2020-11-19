<?php

declare(strict_types=1);

namespace App\Model\Product\Flag;

use App\Model\Product\Product;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

class ProductFlagFacade
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;

    /**
     * @var \App\Model\Product\Flag\ProductFlagFactory
     */
    private $productFlagFactory;

    /**
     * @var \App\Model\Product\Flag\ProductFlagRepository
     */
    private $productFlagRepository;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \App\Model\Product\Flag\ProductFlagFactory $productFlagFactory
     * @param \App\Model\Product\Flag\ProductFlagRepository $productFlagRepository
     */
    public function __construct(EntityManagerInterface $em, ProductFlagFactory $productFlagFactory, ProductFlagRepository $productFlagRepository)
    {
        $this->em = $em;
        $this->productFlagFactory = $productFlagFactory;
        $this->productFlagRepository = $productFlagRepository;
    }

    /**
     * @param \App\Model\Product\Flag\ProductFlagData $productFlagData
     * @param \App\Model\Product\Product $product
     * @return \App\Model\Product\Flag\ProductFlag
     */
    public function create(ProductFlagData $productFlagData, Product $product): ProductFlag
    {
        $productFlag = $this->productFlagFactory->create($productFlagData, $product);
        $this->em->persist($productFlag);
        $this->em->flush();

        return $productFlag;
    }

    /**
     * @param \App\Model\Product\Flag\ProductFlag $productFlag
     * @param \DateTime|null $activeFrom
     * @param \DateTime|null $activeTo
     */
    public function edit(ProductFlag $productFlag, ?DateTime $activeFrom, ?DateTime $activeTo): void
    {
        $productFlag->setActiveFrom($activeFrom);
        $productFlag->setActiveTo($activeTo);
        $this->em->flush();
    }

    /**
     * @param \App\Model\Product\Flag\Flag $flag
     */
    public function deleteByFlag(Flag $flag): void
    {
        $this->productFlagRepository->deleteByFlag($flag);
    }

    /**
     * @param \App\Model\Product\Product $product
     * @return \App\Model\Product\Flag\ProductFlag[]
     */
    public function getByProduct(Product $product): array
    {
        return $this->productFlagRepository->getByProduct($product);
    }
}
