<?php

declare(strict_types=1);

namespace App\Model\Heureka;

use Doctrine\ORM\EntityManagerInterface;

class HeurekaReviewRepository
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
     * @return \Doctrine\ORM\EntityRepository
     */
    protected function getHeurekaReviewRepository()
    {
        return $this->em->getRepository(HeurekaReview::class);
    }

    /**
     * @return \App\Model\Heureka\HeurekaReview[]
     */
    public function getAll(): array
    {
        return $this->getHeurekaReviewRepository()->findAll();
    }

    /**
     * @return \App\Model\Heureka\HeurekaReview[]
     */
    public function getLatestReviews(): array
    {
        return $this->getHeurekaReviewRepository()->findBy([], ['addedAt' => 'DESC'], HeurekaReview::HEUREKA_REVIEW_LIMIT, 0);
    }
}
