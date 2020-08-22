<?php

declare(strict_types=1);

namespace App\Model\Sitemap;

use Doctrine\ORM\Query\Expr\Join;
use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;
use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrl;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup;
use Shopsys\FrameworkBundle\Model\Sitemap\SitemapRepository as BaseSitemapRepository;

/**
 * @property \App\Model\Product\ProductRepository $productRepository
 * @property \App\Model\Category\CategoryRepository $categoryRepository
 * @property \App\Model\Article\ArticleRepository $articleRepository
 * @method __construct(\App\Model\Product\ProductRepository $productRepository, \App\Model\Category\CategoryRepository $categoryRepository, \App\Model\Article\ArticleRepository $articleRepository)
 */
class SitemapRepository extends BaseSitemapRepository
{
    /**
     * Override removes products with sellingDenied from sitemap
     *
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \Shopsys\FrameworkBundle\Model\Sitemap\SitemapItem[]
     */
    public function getSitemapItemsForVisibleProducts(DomainConfig $domainConfig, PricingGroup $pricingGroup)
    {
        $queryBuilder = $this->productRepository->getAllVisibleQueryBuilder($domainConfig->getId(), $pricingGroup);
        $queryBuilder->andWhere('p.sellingDenied = FALSE');
        $queryBuilder
            ->select('fu.slug')
            ->join(
                FriendlyUrl::class,
                'fu',
                Join::WITH,
                'fu.routeName = :productDetailRouteName
                AND fu.entityId = p.id
                AND fu.domainId = :domainId
                AND fu.main = TRUE'
            )
            ->setParameter('productDetailRouteName', 'front_product_detail')
            ->setParameter('domainId', $domainConfig->getId());

        return $this->getSitemapItemsFromQueryBuilderWithSlugField($queryBuilder);
    }
}
