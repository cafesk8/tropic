<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Feed\Google;

use Doctrine\ORM\Query\Expr\Join;
use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\ProductFeed\GoogleBundle\Model\Product\GoogleProductDomain;
use Shopsys\ProductFeed\GoogleBundle\Model\Product\GoogleProductRepository as BaseGoogleProductRepository;

class GoogleProductRepository extends BaseGoogleProductRepository
{
    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param int|null $lastSeekId
     * @param int $maxResults
     * @return iterable
     */
    public function getProducts(DomainConfig $domainConfig, PricingGroup $pricingGroup, ?int $lastSeekId, int $maxResults): iterable
    {
        $queryBuilder = $this->productRepository->getAllVisibleQueryBuilder($domainConfig->getId(), $pricingGroup)
            ->addSelect('v')->join('p.vat', 'v')
            ->addSelect('b')->leftJoin('p.brand', 'b')
            ->leftJoin(GoogleProductDomain::class, 'gpd', Join::WITH, 'gpd.product = p AND gpd.domainId = :domainId')
            ->andWhere('p.variantType != :variantTypeMain')->setParameter('variantTypeMain', Product::VARIANT_TYPE_MAIN)
            ->andWhere('gpd IS NULL OR gpd.show = TRUE')
            ->andWhere('p.catnum IS NOT NULL')
            ->orderBy('p.id', 'asc')
            ->setMaxResults($maxResults);

        $this->productRepository->addTranslation($queryBuilder, $domainConfig->getLocale());
        $this->productRepository->addDomain($queryBuilder, $domainConfig->getId());

        if ($lastSeekId !== null) {
            $queryBuilder->andWhere('p.id > :lastProductId')->setParameter('lastProductId', $lastSeekId);
        }

        return $queryBuilder->getQuery()->execute();
    }
}
