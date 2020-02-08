<?php

declare(strict_types=1);

namespace App\Model\Advert;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Image\ImageFacade;
use Shopsys\FrameworkBundle\Model\Advert\AdvertData;
use Shopsys\FrameworkBundle\Model\Advert\AdvertFacade as BaseAdvertFacade;
use Shopsys\FrameworkBundle\Model\Advert\AdvertFactoryInterface;
use Shopsys\FrameworkBundle\Model\Advert\AdvertPositionRegistry;
use Shopsys\FrameworkBundle\Model\Advert\AdvertRepository;
use Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser;
use App\Model\Advert\Product\AdvertProduct;
use App\Model\Advert\Product\AdvertProductRepository;
use App\Model\Product\ProductRepository;

/**
 * @property \App\Component\Image\ImageFacade $imageFacade
 * @property \App\Model\Advert\AdvertPositionRegistry $advertPositionRegistry
 * @method \App\Model\Advert\Advert getById(int $advertId)
 * @method \App\Model\Advert\Advert|null findRandomAdvertByPositionOnCurrentDomain(string $positionName)
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
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\FrameworkBundle\Model\Advert\AdvertRepository $advertRepository
     * @param \App\Component\Image\ImageFacade $imageFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\Advert\AdvertFactoryInterface $advertFactory
     * @param \App\Model\Advert\AdvertPositionRegistry $advertPositionRegistry
     * @param \App\Model\Advert\Product\AdvertProductRepository $advertProductRepository
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser $currentCustomerUser
     * @param \App\Model\Product\ProductRepository $productRepository
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
        ProductRepository $productRepository
    ) {
        parent::__construct($em, $advertRepository, $imageFacade, $domain, $advertFactory, $advertPositionRegistry);

        $this->advertProductRepository = $advertProductRepository;
        $this->currentCustomerUser = $currentCustomerUser;
        $this->productRepository = $productRepository;
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

        return $advert;
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
}
