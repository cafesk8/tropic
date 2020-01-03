<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Flag;

use Shopsys\FrameworkBundle\Model\Product\Flag\FlagRepository as BaseFlagRepository;

class FlagRepository extends BaseFlagRepository
{
    /**
     * @param int[] $exceptIds
     * @return \Shopsys\ShopBundle\Model\Product\Flag\Flag[]
     */
    public function getAllExceptIds(array $exceptIds): array
    {
        return $this->getFlagRepository()->createQueryBuilder('f')
            ->andWhere('f.id NOT IN(:exceptIds)')
            ->setParameter('exceptIds', $exceptIds)
            ->getQuery()->getResult();
    }
}
