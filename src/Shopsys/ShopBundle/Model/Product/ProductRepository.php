<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product;

use Doctrine\ORM\QueryBuilder;
use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup;
use Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter;
use Shopsys\FrameworkBundle\Model\Product\ProductRepository as BaseProductRepository;

class ProductRepository extends BaseProductRepository
{
    /**
     * @param int $domainId
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param array $ids
     * @return \Shopsys\ShopBundle\Model\Product\Product[]
     */
    public function getAllVisibleByIds(int $domainId, PricingGroup $pricingGroup, array $ids): array
    {
        $queryBuilder = $this->getAllVisibleQueryBuilder($domainId, $pricingGroup);
        $queryBuilder
            ->andWhere('p.id IN(:productIds)')
            ->setParameter('productIds', $ids);

        return $queryBuilder->getQuery()->execute();
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
     * @return \Shopsys\ShopBundle\Model\Product\Product[]
     */
    public function getWithEan(int $limit, int $page): array
    {
        $offset = $limit * $page;

        return $this->getProductQueryBuilder()
            ->where('p.ean IS NOT NULL')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->orderBy('p.id', 'ASC')
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
}
