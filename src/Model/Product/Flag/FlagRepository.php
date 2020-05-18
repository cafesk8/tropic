<?php

declare(strict_types=1);

namespace App\Model\Product\Flag;

use Shopsys\FrameworkBundle\Model\Product\Flag\FlagRepository as BaseFlagRepository;

/**
 * @method \App\Model\Product\Flag\Flag|null findById(int $flagId)
 * @method \App\Model\Product\Flag\Flag getById(int $flagId)
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

    /**
     * @return \App\Model\Product\Flag\Flag[]
     */
    public function getAll()
    {
        return $this->getFlagRepository()->findBy([], ['position' => 'asc']);
    }

    /**
     * @return \App\Model\Product\Flag\Flag[]
     */
    public function getSaleFlags(): array
    {
        return $this->getFlagRepository()->findBy(['sale' => true]);
    }
}
