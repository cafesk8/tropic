<?php

declare(strict_types=1);

namespace App\Model\Heureka;

use Doctrine\ORM\EntityManagerInterface;

class HeurekaReviewRepository
{
    protected EntityManagerInterface $em;

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
    protected function getHeurekaReviewRepository()
    {
        return $this->em->getRepository(HeurekaReview::class);
    }

    /**
     * @param int|null $domainId
     * @return \App\Model\Heureka\HeurekaReview[]
     */
    public function getAll(?int $domainId = null): array
    {
        if ($domainId === null) {
            return $this->getHeurekaReviewRepository()->findAll();
        }

        return $this->getHeurekaReviewRepository()->findBy(['domainId' => $domainId]);
    }

    /**
     * @param int $domainId
     * @return \App\Model\Heureka\HeurekaReview[]
     */
    public function getLatestReviews(int $domainId): array
    {
        return $this->getHeurekaReviewRepository()->findBy(['domainId' => $domainId], ['addedAt' => 'DESC'], HeurekaReview::HEUREKA_REVIEW_LIMIT, 0);
    }
}
