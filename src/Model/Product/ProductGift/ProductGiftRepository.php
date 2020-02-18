<?php

declare(strict_types=1);

namespace App\Model\Product\ProductGift;

use App\Component\Domain\DomainHelper;
use App\Model\Product\ProductGift\Exception\ProductGiftNotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Shopsys\FrameworkBundle\Model\Product\ProductTranslation;

class ProductGiftRepository
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
    private function getProductGiftRepository(): EntityRepository
    {
        return $this->em->getRepository(ProductGift::class);
    }

    /**
     * @param int $productGiftId
     * @return \App\Model\Product\ProductGift\ProductGift|null
     */
    public function findById(int $productGiftId): ?ProductGift
    {
        return $this->getProductGiftRepository()->find($productGiftId);
    }

    /**
     * @param int $productGiftId
     * @return \App\Model\Product\ProductGift\ProductGift
     */
    public function getById(int $productGiftId): ProductGift
    {
        $productGift = $this->findById($productGiftId);

        if ($productGift === null) {
            throw new ProductGiftNotFoundException('ProductGift with ID ' . $productGiftId . ' not found.');
        }

        return $productGift;
    }

    /**
     * @param int $domainId
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getQueryBuilderForAdminProductGiftGrid(int $domainId): QueryBuilder
    {
        return $this->em->createQueryBuilder()
            ->select('pg.id, pg.title, t.name, COUNT(p) AS productsCount, pg.active')
            ->from(ProductGift::class, 'pg')
            ->join('pg.gift', 'g')
            ->join('pg.products', 'p')
            ->join(ProductTranslation::class, 't', Join::WITH, 'g = t.translatable AND t.locale = :locale')
            ->andWhere('pg.domainId = :domainId')
            ->setParameters([
                'domainId' => $domainId,
                'locale' => DomainHelper::DOMAIN_ID_TO_LOCALE[$domainId],
            ])
            ->orderBy('pg.title, t.name')
            ->groupBy('pg.id, pg.title, t.name, pg.active');
    }
}
