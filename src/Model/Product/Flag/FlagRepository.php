<?php

declare(strict_types=1);

namespace App\Model\Product\Flag;

use Shopsys\FrameworkBundle\Model\Product\Flag\FlagRepository as BaseFlagRepository;

/**
 * @method \App\Model\Product\Flag\Flag|null findById(int $flagId)
 * @method \App\Model\Product\Flag\Flag getById(int $flagId)
 * @method \App\Model\Product\Flag\Flag[] getAll()
 */
class FlagRepository extends BaseFlagRepository
{
    /**
     * @param int[] $exceptIds
     * @return \App\Model\Product\Flag\Flag[]
     */
    public function getAllExceptIds(array $exceptIds): array
    {
        return $this->getFlagRepository()->createQueryBuilder('f')
            ->andWhere('f.id NOT IN(:exceptIds)')
            ->setParameter('exceptIds', $exceptIds)
            ->getQuery()->getResult();
    }
}
