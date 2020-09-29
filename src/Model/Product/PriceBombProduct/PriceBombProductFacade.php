<?php

declare(strict_types=1);

namespace App\Model\Product\PriceBombProduct;

use App\Twig\Cache\TwigCacheFacade;
use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup;

class PriceBombProductFacade
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator
     */
    private $em;

    /**
     * @var \App\Model\Product\PriceBombProduct\PriceBombProductRepository
     */
    private $priceBombProductRepository;

    /**
     * @var \App\Model\Product\PriceBombProduct\PriceBombProductFactory
     */
    private $priceBombProductFactory;

    /**
     * @var \App\Twig\Cache\TwigCacheFacade
     */
    private TwigCacheFacade $twigCacheFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator $em
     * @param \App\Model\Product\PriceBombProduct\PriceBombProductRepository $priceBombProductRepository
     * @param \App\Model\Product\PriceBombProduct\PriceBombProductFactory $priceBombProductFactory
     * @param \App\Twig\Cache\TwigCacheFacade $twigCacheFacade
     */
    public function __construct(
        EntityManagerInterface $em,
        PriceBombProductRepository $priceBombProductRepository,
        PriceBombProductFactory $priceBombProductFactory,
        TwigCacheFacade $twigCacheFacade
    ) {
        $this->em = $em;
        $this->priceBombProductRepository = $priceBombProductRepository;
        $this->priceBombProductFactory = $priceBombProductFactory;
        $this->twigCacheFacade = $twigCacheFacade;
    }

    /**
     * @param int $domainId
     * @return \App\Model\Product\PriceBombProduct\PriceBombProduct[]
     */
    public function getAll(int $domainId): array
    {
        return $this->priceBombProductRepository->getAll($domainId);
    }

    /**
     * @param int $domainId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param int|null $limit
     * @return \App\Model\Product\Product[]
     */
    public function getPriceBombProducts(int $domainId, PricingGroup $pricingGroup, ?int $limit = null): array
    {
        return $this->priceBombProductRepository->getSellableProductsUsingStockInStockForPriceBombProductsOnDomain($domainId, $pricingGroup, $limit);
    }

    /**
     * @param int $domainId
     * @param \App\Model\Product\Product[] $products
     */
    public function savePriceBombProductsForDomain(int $domainId, array $products): void
    {
        $oldPriceBombProducts = $this->priceBombProductRepository->getAll($domainId);
        foreach ($oldPriceBombProducts as $oldPriceBombProduct) {
            $this->em->remove($oldPriceBombProduct);
        }
        $this->em->flush($oldPriceBombProducts);

        $priceBombProducts = [];
        $position = 1;
        foreach ($products as $product) {
            $priceBombProduct = $this->priceBombProductFactory->create($product, $domainId, $position++);
            $this->em->persist($priceBombProduct);
            $priceBombProducts[] = $priceBombProduct;
        }
        $this->em->flush($priceBombProducts);

        $this->twigCacheFacade->invalidateByKey('priceBombProducts', $domainId);
    }
}
