<?php

declare(strict_types=1);

namespace App\Model\Product\PromoProduct;

use App\Component\Domain\DomainHelper;
use App\Model\Customer\User\CustomerUser;
use App\Model\Product\PromoProduct\Exception\PromoProductNotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Product\ProductTranslation;

class PromoProductRepository
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @return \Doctrine\ORM\EntityRepository
     */
    private function getPromoProductRepository(): EntityRepository
    {
        return $this->em->getRepository(PromoProduct::class);
    }

    /**
     * @param int $promoProductId
     * @return \App\Model\Product\PromoProduct\PromoProduct|null
     */
    public function findById(int $promoProductId): ?PromoProduct
    {
        return $this->getPromoProductRepository()->find($promoProductId);
    }

    /**
     * @param int $promoProductId
     * @return \App\Model\Product\PromoProduct\PromoProduct
     */
    public function getById(int $promoProductId): PromoProduct
    {
        $promoProduct = $this->findById($promoProductId);

        if ($promoProduct === null) {
            throw new PromoProductNotFoundException('PromoProduct with ID ' . $promoProductId . ' not found.');
        }

        return $promoProduct;
    }

    /**
     * @param int $domainId
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getQueryBuilderForAdminPromoProductGrid(int $domainId): QueryBuilder
    {
        return $this->em->createQueryBuilder()
            ->select('pp.id, t.name, pp.price, pp.minimalCartPrice, pp.type')
            ->from(PromoProduct::class, 'pp')
            ->join('pp.product', 'p')
            ->join(ProductTranslation::class, 't', Join::WITH, 'p = t.translatable AND t.locale = :locale')
            ->andWhere('pp.domainId = :domainId')
            ->setParameters([
                'domainId' => $domainId,
                'locale' => DomainHelper::DOMAIN_ID_TO_LOCALE[$domainId],
            ])
            ->groupBy('pp.id, t.name');
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Money\Money $minimalCartPrice
     * @param int $domainId
     * @param \App\Model\Customer\User\CustomerUser|null $customerUser
     * @return \App\Model\Product\PromoProduct\PromoProduct[]
     */
    public function getPromoProductsWithMinimalCartPrice(Money $minimalCartPrice, int $domainId, ?CustomerUser $customerUser): array
    {
        return $this->em->createQueryBuilder()
            ->select('pp')
            ->from(PromoProduct::class, 'pp')
            ->andWhere('pp.minimalCartPrice <= :minimalCartPrice')
            ->andWhere('pp.price IS NOT NULL')
            ->andWhere('pp.minimalCartPrice IS NOT NULL')
            ->andWhere('pp.domainId = :domainId')
            ->andWhere('pp.type IN (:types)')
            ->setParameters([
                'minimalCartPrice' => $minimalCartPrice->getAmount(),
                'domainId' => $domainId,
                'types' => $this->getPromoProductTypesByUser($customerUser),
            ])
            ->getQuery()
            ->getResult();
    }

    /**
     * @param \App\Model\Customer\User\CustomerUser|null $customerUser
     * @return string[]
     */
    private function getPromoProductTypesByUser(?CustomerUser $customerUser): array
    {
        if ($customerUser === null || !$customerUser->isMemberOfLoyaltyProgram()) {
            return [PromoProduct::TYPE_ALL];
        }

        return array_values(PromoProduct::getTypesIndexedByTitles());
    }
}