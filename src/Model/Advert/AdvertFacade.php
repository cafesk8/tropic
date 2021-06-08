<?php

declare(strict_types=1);

namespace App\Model\Advert;

use App\Model\Advert\Product\AdvertProduct;
use App\Model\Advert\Product\AdvertProductRepository;
use App\Model\Category\CategoryFacade;
use App\Model\Product\ProductRepository;
use App\Twig\Cache\TwigCacheFacade;
use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Image\ImageFacade;
use Shopsys\FrameworkBundle\Model\Advert\AdvertData;
use Shopsys\FrameworkBundle\Model\Advert\AdvertFacade as BaseAdvertFacade;
use Shopsys\FrameworkBundle\Model\Advert\AdvertFactoryInterface;
use Shopsys\FrameworkBundle\Model\Advert\AdvertRepository;
use Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser;

/**
 * @property \App\Component\Image\ImageFacade $imageFacade
 * @property \App\Model\Advert\AdvertPositionRegistry $advertPositionRegistry
 * @method \App\Model\Advert\Advert|null findRandomAdvertByPositionOnCurrentDomain(string $positionName)
 * @method \App\Model\Advert\Advert getById($advertId)
 */
class AdvertFacade extends BaseAdvertFacade
{
    /**
     * @var \App\Model\Advert\Product\AdvertProductRepository
     */
    private $advertProductRepository;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser
     */
    private $currentCustomerUser;

    /**
     * @var \App\Model\Product\ProductRepository
     */
    private $productRepository;

    /**
     * @var \App\Model\Category\CategoryFacade
     */
    private $categoryFacade;

    /**
     * @var \App\Twig\Cache\TwigCacheFacade
     */
    private TwigCacheFacade $twigCacheFacade;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\FrameworkBundle\Model\Advert\AdvertRepository $advertRepository
     * @param \App\Component\Image\ImageFacade $imageFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\Advert\AdvertFactoryInterface $advertFactory
     * @param \App\Model\Advert\AdvertPositionRegistry $advertPositionRegistry
     * @param \App\Model\Advert\Product\AdvertProductRepository $advertProductRepository
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser $currentCustomerUser
     * @param \App\Model\Product\ProductRepository $productRepository
     * @param \App\Model\Category\CategoryFacade $categoryFacade
     * @param \App\Twig\Cache\TwigCacheFacade $twigCacheFacade
     */
    public function __construct(
        EntityManagerInterface $em,
        AdvertRepository $advertRepository,
        ImageFacade $imageFacade,
        Domain $domain,
        AdvertFactoryInterface $advertFactory,
        AdvertPositionRegistry $advertPositionRegistry,
        AdvertProductRepository $advertProductRepository,
        CurrentCustomerUser $currentCustomerUser,
        ProductRepository $productRepository,
        CategoryFacade $categoryFacade,
        TwigCacheFacade $twigCacheFacade
    ) {
        parent::__construct($em, $advertRepository, $imageFacade, $domain, $advertFactory, $advertPositionRegistry);

        $this->advertProductRepository = $advertProductRepository;
        $this->currentCustomerUser = $currentCustomerUser;
        $this->productRepository = $productRepository;
        $this->categoryFacade = $categoryFacade;
        $this->twigCacheFacade = $twigCacheFacade;
    }

    /**
     * @param \App\Model\Advert\AdvertData $advertData
     * @return \App\Model\Advert\Advert
     */
    public function create(AdvertData $advertData): Advert
    {
        /** @var \App\Model\Advert\Advert $advert */
        $advert = parent::create($advertData);

        $this->refreshAdvertProducts($advert, $advertData->products);
        $this->refreshAdvertCategories($advert, $advertData->categories);
        $this->imageFacade->manageImages($advert, $advertData->mobileImage, Advert::TYPE_MOBILE);

        if (in_array($advert->getPositionName(), $this->getCachedPositions(), true)) {
            $this->twigCacheFacade->invalidateByKey('bannersOnHomepage', $advert->getDomainId());
        }

        return $advert;
    }

    /**
     * @param int $advertId
     * @param \App\Model\Advert\AdvertData $advertData
     * @return \App\Model\Advert\Advert
     */
    public function edit($advertId, AdvertData $advertData): Advert
    {
        /** @var \App\Model\Advert\Advert $advert */
        $advert = parent::edit($advertId, $advertData);

        $this->refreshAdvertProducts($advert, $advertData->products);
        $this->refreshAdvertCategories($advert, $advertData->categories);
        $this->imageFacade->manageImages($advert, $advertData->mobileImage, Advert::TYPE_MOBILE);

        if (in_array($advert->getPositionName(), $this->getCachedPositions(), true)) {
            $this->twigCacheFacade->invalidateByKey('bannersOnHomepage', $advert->getDomainId());
        }

        return $advert;
    }

    /**
     * @param int $advertId
     */
    public function delete($advertId)
    {
        $advert = $this->advertRepository->getById($advertId);
        $this->em->remove($advert);
        $this->em->flush();

        if (in_array($advert->getPositionName(), $this->getCachedPositions(), true)) {
            $this->twigCacheFacade->invalidateByKey('bannersOnHomepage', $advert->getDomainId());
        }
    }

    /**
     * @param \App\Model\Advert\Advert|null $advert
     * @return \App\Model\Product\Product[]
     */
    public function getAdvertProductsByAdvert(?Advert $advert): array
    {
        $sellableProductsForAdvert = [];

        if ($advert === null) {
            return $sellableProductsForAdvert;
        }

        $allAdvertProducts = $this->advertProductRepository->getAdvertProductsByAdvert($advert);

        foreach ($allAdvertProducts as $advertProduct) {
            if ($advertProduct->getProduct()->isMainVariant()) {
                $sellableVariants = $this->productRepository->getAllSellableVariantsByMainVariant(
                    $advertProduct->getProduct(),
                    $this->domain->getId(),
                    $this->currentCustomerUser->getPricingGroup()
                );

                if (count($sellableVariants) > 0) {
                    $sellableProductsForAdvert[] = $advertProduct->getProduct();
                }
            } else {
                $sellableProductsForAdvert[] = $advertProduct->getProduct();
            }
        }

        return $sellableProductsForAdvert;
    }

    /**
     * @param \App\Model\Advert\Advert $advert
     * @param \App\Model\Product\Product[] $products
     */
    private function refreshAdvertProducts(Advert $advert, array $products): void
    {
        $oldAdvertProducts = $this->advertProductRepository->getAdvertProductsByAdvert($advert);
        foreach ($oldAdvertProducts as $oldAdvertProduct) {
            $this->em->remove($oldAdvertProduct);
        }
        $this->em->flush();

        foreach ($products as $position => $product) {
            $newLandingPageProduct = new AdvertProduct($advert, $product, $position);
            $this->em->persist($newLandingPageProduct);
        }
        $this->em->flush();
    }

    /**
     * @param \App\Model\Advert\Advert $advert
     * @param \App\Model\Category\Category[] $categories
     */
    private function refreshAdvertCategories(Advert $advert, array $categories): void
    {
        if ($advert->getPositionName() !== AdvertPositionRegistry::CATEGORY_ADVERT_POSITION) {
            $categories = [];
        }

        $this->categoryFacade->removeAdvertFromCategories($advert, $categories);
        $categoryDomains = [];

        foreach ($categories as $category) {
            $categoryDomain = $this->categoryFacade->getCategoryDomainByCategoryAndDomainId($category, $advert->getDomainId());
            $categoryDomain->setAdvert($advert);
            $categoryDomains[] = $categoryDomain;
        }

        $advert->setCategoryDomains($categoryDomains);
        $this->em->flush();
    }

    /**
     * @return string[]
     */
    private function getCachedPositions(): array
    {
        return [
            'firstSquare',
            'secondSquare',
            'thirdSquare',
            'fourthRectangle',
        ];
    }
}
