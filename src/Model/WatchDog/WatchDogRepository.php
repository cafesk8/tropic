<?php

declare(strict_types=1);

namespace App\Model\WatchDog;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Shopsys\FrameworkBundle\Model\Product\ProductVisibility;

class WatchDogRepository
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    protected $em;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @return \App\Model\WatchDog\WatchDog[]
     */
    public function getAllVisible(): array
    {
        return $this->getWatchDogRepository()
            ->createQueryBuilder('wd')
            ->join(ProductVisibility::class, 'pv', Join::WITH, 'pv.product = wd.product')
            ->where('pv.pricingGroup = wd.pricingGroup')
            ->andWhere('pv.visible = TRUE')
            ->getQuery()->execute();
    }

    /**
     * @return \Doctrine\ORM\EntityRepository
     */
    private function getWatchDogRepository(): EntityRepository
    {
        return $this->em->getRepository(WatchDog::class);
    }
}
