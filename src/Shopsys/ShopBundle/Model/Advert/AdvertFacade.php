<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Advert;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Image\ImageFacade;
use Shopsys\FrameworkBundle\Model\Advert\AdvertData;
use Shopsys\FrameworkBundle\Model\Advert\AdvertFacade as BaseAdvertFacade;
use Shopsys\FrameworkBundle\Model\Advert\AdvertFactoryInterface;
use Shopsys\FrameworkBundle\Model\Advert\AdvertPositionRegistry;
use Shopsys\FrameworkBundle\Model\Advert\AdvertRepository;
use Shopsys\ShopBundle\Model\Advert\Product\AdvertProduct;
use Shopsys\ShopBundle\Model\Advert\Product\AdvertProductRepository;

class AdvertFacade extends BaseAdvertFacade
{
    /**
     * @var \Shopsys\ShopBundle\Model\Advert\Product\AdvertProductRepository
     */
    private $advertProductRepository;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\FrameworkBundle\Model\Advert\AdvertRepository $advertRepository
     * @param \Shopsys\FrameworkBundle\Component\Image\ImageFacade $imageFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\Advert\AdvertFactoryInterface $advertFactory
     * @param \Shopsys\FrameworkBundle\Model\Advert\AdvertPositionRegistry $advertPositionRegistry
     * @param \Shopsys\ShopBundle\Model\Advert\Product\AdvertProductRepository $advertProductRepository
     */
    public function __construct(
        EntityManagerInterface $em,
        AdvertRepository $advertRepository,
        ImageFacade $imageFacade,
        Domain $domain,
        AdvertFactoryInterface $advertFactory,
        AdvertPositionRegistry $advertPositionRegistry,
        AdvertProductRepository $advertProductRepository
    ) {
        parent::__construct($em, $advertRepository, $imageFacade, $domain, $advertFactory, $advertPositionRegistry);

        $this->advertProductRepository = $advertProductRepository;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Advert\AdvertData $advertData
     * @return \Shopsys\ShopBundle\Model\Advert\Advert
     */
    public function create(AdvertData $advertData): Advert
    {
        $advert = parent::create($advertData);

        $this->refreshAdvertProducts($advert, $advertData->products);

        return $advert;
    }

    /**
     * @param int $advertId
     * @param \Shopsys\ShopBundle\Model\Advert\AdvertData $advertData
     * @return \Shopsys\ShopBundle\Model\Advert\Advert
     */
    public function edit($advertId, AdvertData $advertData): Advert
    {
        $advert = parent::edit($advertId, $advertData);

        $this->refreshAdvertProducts($advert, $advertData->products);

        return $advert;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Advert\Advert $advert
     * @param \Shopsys\ShopBundle\Model\Product\Product[] $products
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
