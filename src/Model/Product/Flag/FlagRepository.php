<?php

declare(strict_types=1);

namespace App\Model\Product\Flag;

use Doctrine\ORM\Query\Expr\Join;
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
     * @return \App\Model\Product\Flag\Flag|null
     */
    public function findSaleFlag(): ?Flag
    {
        return $this->getFlagRepository()->findOneBy(['pohodaId' => Flag::POHODA_ID_DISCOUNT]);
    }

    /**
     * @return \App\Model\Product\Flag\Flag|null
     */
    public function findNewsFlag(): ?Flag
    {
        return $this->getFlagRepository()->findOneBy(['pohodaId' => Flag::POHODA_ID_NEW]);
    }

    /**
     * @param int[] $flagsIds
     * @param string $locale
     * @return \App\Model\Product\Flag\Flag[]
     */
    public function getFlagsForFilterByIds(array $flagsIds, string $locale): array
    {
        $flagsQueryBuilder = $this->getFlagRepository()->createQueryBuilder('f')
            ->select('f, ft')
            ->join('f.translations', 'ft', Join::WITH, 'ft.locale = :locale')
            ->where('f.id IN (:flagsIds)')
            ->andWhere('f.visible = true')
            ->orderBy('ft.name', 'asc')
            ->setParameter('flagsIds', $flagsIds)
            ->setParameter('locale', $locale);

        return $flagsQueryBuilder->getQuery()->getResult();
    }
}
