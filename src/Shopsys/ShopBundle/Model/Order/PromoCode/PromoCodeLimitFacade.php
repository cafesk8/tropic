<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\PromoCode;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\ShopBundle\Model\Category\Category;
use Shopsys\ShopBundle\Model\Product\Brand\Brand;
use Shopsys\ShopBundle\Model\Product\Product;

class PromoCodeLimitFacade
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;

    /**
     * @var \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeLimitRepository
     */
    private $promoCodeLimitRepository;

    /**
     * @var \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeLimitFactory
     */
    private $promoCodeLimitFactory;

    /**
     * @var \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeLimitDataFactory
     */
    private $promoCodeLimitDataFactory;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeLimitRepository $promoCodeRepository
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeLimitFactory $promoCodeLimitFactory
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeLimitDataFactory $promoCodeLimitDataFactory
     */
    public function __construct(EntityManagerInterface $em, PromoCodeLimitRepository $promoCodeRepository, PromoCodeLimitFactory $promoCodeLimitFactory, PromoCodeLimitDataFactory $promoCodeLimitDataFactory)
    {
        $this->em = $em;
        $this->promoCodeLimitRepository = $promoCodeRepository;
        $this->promoCodeLimitFactory = $promoCodeLimitFactory;
        $this->promoCodeLimitDataFactory = $promoCodeLimitDataFactory;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeLimitData $promoCodeLimitData
     * @return \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeLimit
     */
    public function create(PromoCodeLimitData $promoCodeLimitData)
    {
        $promoCodeLimit = $this->promoCodeLimitFactory->create($promoCodeLimitData);
        $this->em->persist($promoCodeLimit);
        $this->em->flush();

        return $promoCodeLimit;
    }

    /**
     * @param int $id
     * @return \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeLimit
     */
    public function getById(int $id)
    {
        return $this->promoCodeLimitRepository->getById($id);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode $promoCode
     * @return \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeLimit[]
     */
    public function getByPromoCode(PromoCode $promoCode)
    {
        return $this->promoCodeLimitRepository->getByPromoCode($promoCode);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode $promoCode
     */
    public function deleteByPromoCode(PromoCode $promoCode)
    {
        $this->promoCodeLimitRepository->deleteByPromoCode($promoCode);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode $promoCode
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeData $promoCodeData
     */
    public function refreshLimits(PromoCode $promoCode, PromoCodeData $promoCodeData)
    {
        $this->deleteByPromoCode($promoCode);

        switch ($promoCodeData->limitType) {
            case PromoCode::LIMIT_TYPE_BRANDS:
                foreach ($promoCodeData->brandLimits as $brand) {
                    $this->createFromPromoCodeAndBrand($promoCode, $brand);
                }
                break;
            case PromoCode::LIMIT_TYPE_CATEGORIES:
                foreach ($promoCodeData->categoryLimits as $category) {
                    $this->createFromPromoCodeAndCategory($promoCode, $category);
                }
                break;
            case PromoCode::LIMIT_TYPE_PRODUCTS:
                foreach ($promoCodeData->productLimits as $product) {
                    $this->createFromPromoCodeAndProduct($promoCode, $product);
                }
                break;
        }
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode $promoCode
     * @param \Shopsys\ShopBundle\Model\Product\Brand\Brand $brand
     * @return \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeLimit
     */
    private function createFromPromoCodeAndBrand(PromoCode $promoCode, Brand $brand)
    {
        return $this->createFromPromoCodeAndObjectId($promoCode, $brand->getId(), PromoCode::LIMIT_TYPE_BRANDS);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode $promoCode
     * @param \Shopsys\ShopBundle\Model\Category\Category $category
     * @return \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeLimit
     */
    private function createFromPromoCodeAndCategory(PromoCode $promoCode, Category $category)
    {
        return $this->createFromPromoCodeAndObjectId($promoCode, $category->getId(), PromoCode::LIMIT_TYPE_CATEGORIES);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode $promoCode
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @return \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeLimit
     */
    private function createFromPromoCodeAndProduct(PromoCode $promoCode, Product $product)
    {
        return $this->createFromPromoCodeAndObjectId($promoCode, $product->getId(), PromoCode::LIMIT_TYPE_PRODUCTS);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode $promoCode
     * @param int $objectId
     * @param string $type
     * @return \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeLimit
     */
    private function createFromPromoCodeAndObjectId(PromoCode $promoCode, int $objectId, string $type)
    {
        $promoCodeLimitData = $this->promoCodeLimitDataFactory->create();
        $promoCodeLimitData->promoCode = $promoCode;
        $promoCodeLimitData->objectId = $objectId;
        $promoCodeLimitData->type = $type;

        return $this->create($promoCodeLimitData);
    }
}
