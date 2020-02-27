<?php

declare(strict_types=1);

namespace App\Model\Order\Gift;

use App\Model\Order\Exception\OrderGiftNotFoundException;
use App\Model\Product\ProductRepository;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\FrameworkBundle\Model\Product\ProductVisibility;

class OrderGiftRepository
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator
     */
    protected $entityManager;

    /**
     * @var \App\Model\Product\ProductRepository
     */
    protected $productRepository;

    /**
     * @param \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator $entityManager
     * @param \App\Model\Product\ProductRepository $productRepository
     */
    public function __construct(EntityManagerInterface $entityManager, ProductRepository $productRepository)
    {
        $this->entityManager = $entityManager;
        $this->productRepository = $productRepository;
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    private function getOrderGiftRepository(): ObjectRepository
    {
        return $this->entityManager->getRepository(OrderGift::class);
    }

    /**
     * @param int $id
     * @return \App\Model\Order\Gift\OrderGift
     */
    public function getById(int $id): OrderGift
    {
        $orderGift = $this->findById($id);
        if ($orderGift === null) {
            throw new OrderGiftNotFoundException();
        }

        return $orderGift;
    }

    /**
     * @param int $id
     * @return \App\Model\Order\Gift\OrderGift|null
     */
    private function findById(int $id): ?OrderGift
    {
        return $this->getOrderGiftRepository()->find($id);
    }

    /**
     * @param int $domainId
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getQueryBuilderForAdminOrderGiftGrid(int $domainId): QueryBuilder
    {
        return $this->entityManager->createQueryBuilder()
            ->select('og.id as id, og.enabled as enabled, COUNT(p) AS productsCount, og.priceLevelWithVat as priceLevelWithVat')
            ->from(OrderGift::class, 'og')
            ->join('og.products', 'p')
            ->where('og.domainId = :domainId')
            ->setParameter('domainId', $domainId)
            ->groupBy('id')
            ->orderBy('priceLevelWithVat', 'ASC');
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Money\Money $totalProductPriceWithVat
     * @param int $domainId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \App\Model\Product\Product[]
     */
    public function getAllListableGiftProductsByTotalProductPrice(
        Money $totalProductPriceWithVat,
        int $domainId,
        PricingGroup $pricingGroup
    ): array {
        $allLevels = $this->getAllEnabledLevelsOnDomain($domainId);
        $currentLevel = $this->getCurrentLevel($allLevels, $totalProductPriceWithVat);

        if ($currentLevel === null) {
            return [];
        }

        $queryBuilder = $this->getAllListableGiftProductsQueryBuilder($domainId, $pricingGroup)
            ->andWhere('og.priceLevelWithVat = :currentLevel')
            ->setParameter('currentLevel', $currentLevel->getAmount());

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Money\Money $totalProductPriceWithVat
     * @param int $domainId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \App\Model\Product\Product[]
     */
    public function getAllListableNextLevelGiftProductsByTotalProductPrice(
        Money $totalProductPriceWithVat,
        int $domainId,
        PricingGroup $pricingGroup
    ): array {
        $allLevels = $this->getAllEnabledLevelsOnDomain($domainId);
        $currentLevel = $this->getCurrentLevel($allLevels, $totalProductPriceWithVat);
        $nextUpperLevel = $this->getNextUpperLevel($allLevels, $currentLevel);

        if ($nextUpperLevel === null) {
            return [];
        }

        return $this->getAllListableGiftProductsQueryBuilder($domainId, $pricingGroup)
            ->andWhere('og.priceLevelWithVat = :priceLevelWithVat')
            ->setParameter('priceLevelWithVat', $nextUpperLevel->getAmount())
            ->getQuery()->getResult();
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Money\Money $totalProductPriceWithVat
     * @param int $domainId
     * @return \Shopsys\FrameworkBundle\Component\Money\Money|null
     */
    public function getNextLevelDifference(Money $totalProductPriceWithVat, int $domainId): ?Money
    {
        $allLevels = $this->getAllEnabledLevelsOnDomain($domainId);
        $currentLevel = $this->getCurrentLevel($allLevels, $totalProductPriceWithVat);
        $nextUpperLevel = $this->getNextUpperLevel($allLevels, $currentLevel);

        if ($nextUpperLevel === null) {
            return null;
        }

        return $nextUpperLevel->subtract($totalProductPriceWithVat);
    }

    /**
     * @param int $domainId
     * @return \Shopsys\FrameworkBundle\Component\Money\Money[]
     */
    private function getAllEnabledLevelsOnDomain(int $domainId): array
    {
        $allLevels = $this->getAllLevelsOnDomainQueryBuilder($domainId)
            ->andWhere('og.enabled = true')
            ->getQuery()->getResult();

        return array_column($allLevels, 'priceLevelWithVat');
    }

    /**
     * @param int $domainId
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function getAllLevelsOnDomainQueryBuilder(int $domainId): QueryBuilder
    {
        return $this->entityManager->createQueryBuilder()
            ->select('og.priceLevelWithVat')
            ->from(OrderGift::class, 'og')
            ->where('og.domainId = :domainId')
            ->setParameter('domainId', $domainId)
            ->orderBy('og.priceLevelWithVat');
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Money\Money[] $allLevels
     * @param \Shopsys\FrameworkBundle\Component\Money\Money $totalProductPriceWithVat
     * @return \Shopsys\FrameworkBundle\Component\Money\Money|null
     */
    private function getCurrentLevel(array $allLevels, Money $totalProductPriceWithVat): ?Money
    {
        if (count($allLevels) === 0) {
            return null;
        }
        $minLevel = min($allLevels);
        if ($totalProductPriceWithVat->isLessThan($minLevel)) {
            return null;
        }
        $currentLevel = $minLevel;
        foreach ($allLevels as $level) {
            if ($level->isGreaterThan($currentLevel) && $level->isLessThanOrEqualTo($totalProductPriceWithVat)) {
                $currentLevel = $level;
            }
        }

        return $currentLevel;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Money\Money[] $allLevels
     * @param \Shopsys\FrameworkBundle\Component\Money\Money|null $currentLevel
     * @return \Shopsys\FrameworkBundle\Component\Money\Money|null
     */
    private function getNextUpperLevel(array $allLevels, ?Money $currentLevel): ?Money
    {
        if (count($allLevels) === 0) {
            return null;
        }
        if ($currentLevel === null) {
            return min($allLevels);
        }
        foreach ($allLevels as $level) {
            if ($level->isGreaterThan($currentLevel)) {
                return $level;
            }
        }

        return null;
    }

    /**
     * @param int $domainId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function getAllListableGiftProductsQueryBuilder(int $domainId, PricingGroup $pricingGroup): QueryBuilder
    {
        return $this->productRepository->getAllListableQueryBuilder($domainId, $pricingGroup)
            ->resetDQLPart('from')
            ->select('p')
            ->from(OrderGift::class, 'og')
            ->join('og.products', 'p2')
            ->join(Product::class, 'p', Join::WITH, 'p.id = p2.id')
            ->join(ProductVisibility::class, 'prv', Join::WITH, 'prv.product = p.id')
            ->andWhere('og.domainId = :domainId')
            ->andWhere('og.enabled = true');
    }
}
