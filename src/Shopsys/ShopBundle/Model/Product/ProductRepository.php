<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product;

use Doctrine\ORM\QueryBuilder;
use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup;
use Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\FrameworkBundle\Model\Product\ProductRepository as BaseProductRepository;

class ProductRepository extends BaseProductRepository
{
    /**
     * @param int $domainId
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param array $ids
     * @return \Shopsys\ShopBundle\Model\Product\Product[]
     */
    public function getVisibleMainVariantsByIds(int $domainId, PricingGroup $pricingGroup, array $ids): array
    {
        return $this->getAllVisibleQueryBuilder($domainId, $pricingGroup)
            ->andWhere('p.id IN(:productIds)')
            ->andWhere('p.variantType = :variantTypeMain')
            ->setParameter('productIds', $ids)
            ->setParameter('variantTypeMain', Product::VARIANT_TYPE_MAIN)
            ->getQuery()
            ->execute();
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product[] $mainVariants
     * @param int $domainId
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \Shopsys\FrameworkBundle\Model\Product\Product[]
     */
    public function getAllSellableVariantsForMainVariants(array $mainVariants, $domainId, PricingGroup $pricingGroup): array
    {
        $queryBuilder = $this->getAllSellableQueryBuilder($domainId, $pricingGroup);
        $queryBuilder
            ->andWhere('p.mainVariant IN (:mainVariants)')
            ->setParameter('mainVariants', $mainVariants);

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter $parameter
     * @return \Shopsys\ShopBundle\Model\Product\Product[]
     */
    public function getProductsWithDistinguishingParameter(Parameter $parameter): array
    {
        return $this->getProductRepository()->findBy([
            'distinguishingParameter' => $parameter,
        ]);
    }

    /**
     * @param string $transferNumber
     * @return \Shopsys\ShoRpBundle\Model\Product\Product|null
     */
    public function findByTransferNumber(string $transferNumber): ?Product
    {
        return $this->getProductRepository()->findOneBy(['transferNumber' => $transferNumber]);
    }

    /**
     * @param string $ean
     * @return \Shopsys\ShoRpBundle\Model\Product\Product|null
     */
    public function findByEan(string $ean): ?Product
    {
        return $this->getProductRepository()->findOneBy(['ean' => $ean]);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product[] $products
     * @param int $domainId
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \Shopsys\ShopBundle\Model\Product\Product[]
     */
    public function getVariantsIndexedByMainVariantId(array $products, int $domainId, PricingGroup $pricingGroup): array
    {
        $queryBuilder = $this->getAllSellableQueryBuilder($domainId, $pricingGroup);
        $queryBuilder
            ->andWhere('p.mainVariant IN (:mainVariants)')
            ->setParameter('mainVariants', $products);

        $queryResult = $queryBuilder->getQuery()->execute();

        $results = [];

        /** @var \Shopsys\ShopBundle\Model\Product\Product $product */
        foreach ($queryResult as $product) {
            $results[$product->getMainVariant()->getId()][] = $product;
        }

        return $results;
    }

    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getProductQueryBuilder(): QueryBuilder
    {
        return $this->em->createQueryBuilder()
            ->select('p')
            ->from(Product::class, 'p');
    }

    /**
     * @param int $limit
     * @param int $page
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getWithEanQueryBuilder(int $limit, int $page): QueryBuilder
    {
        $offset = $limit * $page;

        return $this->getProductQueryBuilder()
            ->where('p.ean IS NOT NULL')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->orderBy('p.id', 'ASC');
    }

    /**
     * @param int $limit
     * @param int $page
     * @return array
     */
    public function getWithEan(int $limit, int $page): array
    {
        return $this->getWithEanQueryBuilder($limit, $page)->getQuery()->getResult();
    }

    /**
     * @param int $limit
     * @param int $page
     * @return \Shopsys\ShopBundle\Model\Product\Product[]
     */
    public function getMainVariantsWithEan(int $limit, int $page): array
    {
        return $this->getWithEanQueryBuilder($limit, $page)
            ->andWhere('p.variantType = :mainVariantType')
            ->setParameter('mainVariantType', Product::VARIANT_TYPE_MAIN)
            ->getQuery()->getResult();
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param int|null $lastSeekId
     * @param int $maxResults
     * @return \Shopsys\FrameworkBundle\Model\Product\Product[]|\Doctrine\Common\Collections\ArrayCollection
     */
    public function getProductsForHsSportXmlFeed(DomainConfig $domainConfig, PricingGroup $pricingGroup, ?int $lastSeekId, int $maxResults): iterable
    {
        $queryBuilder = $this->getAllVisibleQueryBuilder($domainConfig->getId(), $pricingGroup)
            ->addSelect('v')->join('p.vat', 'v')
            ->addSelect('b')->leftJoin('p.brand', 'b')
            ->andWhere('p.variantType = :variantTypeMain')
            ->setParameter('variantTypeMain', Product::VARIANT_TYPE_MAIN)
            ->andWhere('p.calculatedSellingDenied = false')
            ->andWhere('p.generateToHsSportXmlFeed = true')
            ->orderBy('p.id', 'asc')
            ->setMaxResults($maxResults);

        $this->addTranslation($queryBuilder, $domainConfig->getLocale());
        $this->addDomain($queryBuilder, $domainConfig->getId());

        if ($lastSeekId !== null) {
            $queryBuilder->andWhere('p.id > :lastProductId')->setParameter('lastProductId', $lastSeekId);
        }

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * @param string $ean
     * @return \Shopsys\ShopBundle\Model\Product\Product|null
     */
    public function findOneByEan(string $ean): ?Product
    {
        return $this->getProductRepository()->findOneBy(['ean' => $ean]);
    }

    /**
     * @param string $catnum
     * @return \Shopsys\ShopBundle\Model\Product\Product[]
     */
    public function getByCatnum(string $catnum): array
    {
        return $this->getProductRepository()->findBy(['catnum' => $catnum]);
    }

    /**
     * @param int $limit
     * @param int $domainId
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \Shopsys\ShopBundle\Model\Product\Product[]
     */
    public function getProductsForExportToMall(int $limit, int $domainId, PricingGroup $pricingGroup): array
    {
        return $this->getAllVisibleQueryBuilder($domainId, $pricingGroup)
            ->andWhere('p.mallExport = TRUE')
            ->andWhere('p.mallExportedAt is NULL OR p.mallExportedAt < p.updatedAt')
            ->setMaxResults($limit)
            ->getQuery()->getResult();
    }
}
