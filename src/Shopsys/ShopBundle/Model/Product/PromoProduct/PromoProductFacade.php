<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\PromoProduct;

use Doctrine\ORM\EntityManagerInterface;

class PromoProductFacade
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\PromoProduct\PromoProductRepository
     */
    private $promoProductRepository;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\PromoProduct\PromoProductFactory
     */
    private $promoProductFactory;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\ShopBundle\Model\Product\PromoProduct\PromoProductRepository $promoProductRepository
     * @param \Shopsys\ShopBundle\Model\Product\PromoProduct\PromoProductFactory $promoProductFactory
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
     * @return \Shopsys\ShopBundle\Model\Product\PromoProduct\PromoProduct
     */
    public function getById(int $promoProductId): PromoProduct
    {
        return $this->promoProductRepository->getById($promoProductId);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\PromoProduct\PromoProductData $promoProductData
     * @return \Shopsys\ShopBundle\Model\Product\PromoProduct\PromoProduct
     */
    public function create(PromoProductData $promoProductData): PromoProduct
    {
        $promoProduct = $this->promoProductFactory->create($promoProductData);

        $this->em->persist($promoProduct);
        $this->em->flush();

        return $promoProduct;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\PromoProduct\PromoProduct $promoProduct
     * @param \Shopsys\ShopBundle\Model\Product\PromoProduct\PromoProductData $promoProductData
     * @return \Shopsys\ShopBundle\Model\Product\PromoProduct\PromoProduct
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
