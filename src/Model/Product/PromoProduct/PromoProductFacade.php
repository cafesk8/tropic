<?php

declare(strict_types=1);

namespace App\Model\Product\PromoProduct;

use Doctrine\ORM\EntityManagerInterface;

class PromoProductFacade
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;

    /**
     * @var \App\Model\Product\PromoProduct\PromoProductRepository
     */
    private $promoProductRepository;

    /**
     * @var \App\Model\Product\PromoProduct\PromoProductFactory
     */
    private $promoProductFactory;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \App\Model\Product\PromoProduct\PromoProductRepository $promoProductRepository
     * @param \App\Model\Product\PromoProduct\PromoProductFactory $promoProductFactory
     */
    public function __construct(
        EntityManagerInterface $em,
        PromoProductRepository $promoProductRepository,
        PromoProductFactory $promoProductFactory
    ) {
        $this->em = $em;
        $this->promoProductRepository = $promoProductRepository;
        $this->promoProductFactory = $promoProductFactory;
    }

    /**
     * @param int $promoProductId
     * @return \App\Model\Product\PromoProduct\PromoProduct
     */
    public function getById(int $promoProductId): PromoProduct
    {
        return $this->promoProductRepository->getById($promoProductId);
    }

    /**
     * @param \App\Model\Product\PromoProduct\PromoProductData $promoProductData
     * @return \App\Model\Product\PromoProduct\PromoProduct
     */
    public function create(PromoProductData $promoProductData): PromoProduct
    {
        $promoProduct = $this->promoProductFactory->create($promoProductData);

        $this->em->persist($promoProduct);
        $this->em->flush();

        return $promoProduct;
    }

    /**
     * @param \App\Model\Product\PromoProduct\PromoProduct $promoProduct
     * @param \App\Model\Product\PromoProduct\PromoProductData $promoProductData
     * @return \App\Model\Product\PromoProduct\PromoProduct
     */
    public function edit(PromoProduct $promoProduct, PromoProductData $promoProductData): PromoProduct
    {
        $promoProduct->edit($promoProductData);

        $this->em->flush();

        return $promoProduct;
    }

    /**
     * @param int $promoProductId
     */
    public function delete(int $promoProductId): void
    {
        $promoProduct = $this->promoProductRepository->getById($promoProductId);

        $this->em->remove($promoProduct);
        $this->em->flush();
    }
}
