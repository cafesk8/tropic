<?php

declare(strict_types=1);

namespace App\Model\Order\PromoCode;

use App\Model\Category\Category;
use App\Model\Product\Brand\Brand;
use App\Model\Product\Product;
use App\Model\Product\ProductFacade;
use Doctrine\ORM\EntityManagerInterface;

class PromoCodeLimitFacade
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;

    /**
     * @var \App\Model\Order\PromoCode\PromoCodeLimitRepository
     */
    private $promoCodeLimitRepository;

    /**
     * @var \App\Model\Order\PromoCode\PromoCodeLimitFactory
     */
    private $promoCodeLimitFactory;

    /**
     * @var \App\Model\Order\PromoCode\PromoCodeLimitDataFactory
     */
    private $promoCodeLimitDataFactory;

    /**
     * @var \App\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \App\Model\Order\PromoCode\PromoCodeLimitRepository $promoCodeRepository
     * @param \App\Model\Order\PromoCode\PromoCodeLimitFactory $promoCodeLimitFactory
     * @param \App\Model\Order\PromoCode\PromoCodeLimitDataFactory $promoCodeLimitDataFactory
     * @param \App\Model\Product\ProductFacade $productFacade
     */
    public function __construct(EntityManagerInterface $em, PromoCodeLimitRepository $promoCodeRepository, PromoCodeLimitFactory $promoCodeLimitFactory, PromoCodeLimitDataFactory $promoCodeLimitDataFactory, ProductFacade $productFacade)
    {
        $this->em = $em;
        $this->promoCodeLimitRepository = $promoCodeRepository;
        $this->promoCodeLimitFactory = $promoCodeLimitFactory;
        $this->promoCodeLimitDataFactory = $promoCodeLimitDataFactory;
        $this->productFacade = $productFacade;
    }

    /**
     * @param \App\Model\Order\PromoCode\PromoCodeLimitData $promoCodeLimitData
     * @return \App\Model\Order\PromoCode\PromoCodeLimit
     */
    public function create(PromoCodeLimitData $promoCodeLimitData): PromoCodeLimit
    {
        $promoCodeLimit = $this->promoCodeLimitFactory->create($promoCodeLimitData);
        $this->em->persist($promoCodeLimit);
        $this->em->flush();

        return $promoCodeLimit;
    }

    /**
     * @param int $id
     * @return \App\Model\Order\PromoCode\PromoCodeLimit
     */
    public function getById(int $id): PromoCodeLimit
    {
        return $this->promoCodeLimitRepository->getById($id);
    }

    /**
     * @param \App\Model\Order\PromoCode\PromoCode $promoCode
     * @return \App\Model\Order\PromoCode\PromoCodeLimit[]
     */
    public function getByPromoCode(PromoCode $promoCode): array
    {
        return $this->promoCodeLimitRepository->getByPromoCode($promoCode);
    }

    /**
     * @param \App\Model\Order\PromoCode\PromoCodeLimit[] $limits
     * @return \App\Model\Product\Product[]
     */
    public function getAllApplicableProductsByLimits(array $limits): array
    {
        $brandIds = [];
        $categoryIds = [];
        $productIds = [];
        $products = [];

        foreach ($limits as $limit) {
            switch ($limit->getType()) {
                case PromoCode::LIMIT_TYPE_BRANDS:
                    $brandIds[] = $limit->getObjectId();
                    break;
                case PromoCode::LIMIT_TYPE_CATEGORIES:
                    $categoryIds[] = $limit->getObjectId();
                    break;
                case PromoCode::LIMIT_TYPE_PRODUCTS:
                    $productIds[] = $limit->getObjectId();
                    break;
            }
        }

        if (!empty($brandIds)) {
            $products += $this->productFacade->getByBrandIdsIndexedById($brandIds);
        }

        if (!empty($categoryIds)) {
            $products += $this->productFacade->getByCategoryIdsIndexedById($categoryIds);
        }

        if (!empty($productIds)) {
            $products += $this->productFacade->getByIdsIndexedById($productIds);
        }

        return $products;
    }

    /**
     * @param \App\Model\Order\PromoCode\PromoCode $promoCode
     */
    public function deleteByPromoCode(PromoCode $promoCode)
    {
        $this->promoCodeLimitRepository->deleteByPromoCode($promoCode);
    }

    /**
     * @param \App\Model\Order\PromoCode\PromoCode $promoCode
     * @param \App\Model\Order\PromoCode\PromoCodeData $promoCodeData
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
     * @param \App\Model\Order\PromoCode\PromoCode $promoCode
     * @param \App\Model\Product\Brand\Brand $brand
     * @return \App\Model\Order\PromoCode\PromoCodeLimit
     */
    private function createFromPromoCodeAndBrand(PromoCode $promoCode, Brand $brand): PromoCodeLimit
    {
        return $this->createFromPromoCodeAndObjectId($promoCode, $brand->getId(), PromoCode::LIMIT_TYPE_BRANDS);
    }

    /**
     * @param \App\Model\Order\PromoCode\PromoCode $promoCode
     * @param \App\Model\Category\Category $category
     * @return \App\Model\Order\PromoCode\PromoCodeLimit
     */
    private function createFromPromoCodeAndCategory(PromoCode $promoCode, Category $category): PromoCodeLimit
    {
        return $this->createFromPromoCodeAndObjectId($promoCode, $category->getId(), PromoCode::LIMIT_TYPE_CATEGORIES);
    }

    /**
     * @param \App\Model\Order\PromoCode\PromoCode $promoCode
     * @param \App\Model\Product\Product $product
     * @return \App\Model\Order\PromoCode\PromoCodeLimit
     */
    private function createFromPromoCodeAndProduct(PromoCode $promoCode, Product $product): PromoCodeLimit
    {
        return $this->createFromPromoCodeAndObjectId($promoCode, $product->getId(), PromoCode::LIMIT_TYPE_PRODUCTS);
    }

    /**
     * @param \App\Model\Order\PromoCode\PromoCode $promoCode
     * @param int $objectId
     * @param string $type
     * @return \App\Model\Order\PromoCode\PromoCodeLimit
     */
    private function createFromPromoCodeAndObjectId(PromoCode $promoCode, int $objectId, string $type): PromoCodeLimit
    {
        $promoCodeLimitData = $this->promoCodeLimitDataFactory->create();
        $promoCodeLimitData->promoCode = $promoCode;
        $promoCodeLimitData->objectId = $objectId;
        $promoCodeLimitData->type = $type;

        return $this->create($promoCodeLimitData);
    }
}
