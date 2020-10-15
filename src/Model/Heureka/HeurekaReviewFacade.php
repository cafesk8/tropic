<?php

declare(strict_types=1);

namespace App\Model\Heureka;

use App\Component\Domain\DomainHelper;
use Doctrine\ORM\EntityManagerInterface;

class HeurekaReviewFacade
{
    public const HEUREKA_REVIEWS_URLS = [
        DomainHelper::CZECH_DOMAIN => 'https://obchody.heureka.cz/tropicliberec-cz/recenze/',
        DomainHelper::SLOVAK_DOMAIN => 'https://obchody.heureka.sk/tropicliberec-sk/recenze/',
    ];

    private EntityManagerInterface $em;

    private HeurekaReviewRepository $heurekaReviewRepository;

    private HeurekaReviewFactory $heurekaReviewFactory;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \App\Model\Heureka\HeurekaReviewRepository $heurekaReviewRepository
     * @param \App\Model\Heureka\HeurekaReviewFactory $heurekaReviewFactory
     */
    public function __construct(
        EntityManagerInterface $em,
        HeurekaReviewRepository $heurekaReviewRepository,
        HeurekaReviewFactory $heurekaReviewFactory
    ){
        $this->em = $em;
        $this->heurekaReviewRepository = $heurekaReviewRepository;
        $this->heurekaReviewFactory = $heurekaReviewFactory;
    }

    /**
     * @param \App\Model\Heureka\HeurekaReviewItem $reviewItem
     * @return \App\Model\Heureka\HeurekaReview
     */
    public function create(HeurekaReviewItem $reviewItem): HeurekaReview
    {
        $review = $this->heurekaReviewFactory->create($reviewItem);

        $this->em->persist($review);
        $this->em->flush();

        return $review;
    }

    /**
     * @param int|null $domainId
     * @return \App\Model\Heureka\HeurekaReview[]
     */
    public function getAll(?int $domainId = null): array
    {
        return $this->heurekaReviewRepository->getAll($domainId);
    }

    /**
     * @param int $domainId
     * @return \App\Model\Heureka\HeurekaReview[]
     */
    public function getLatestReviews(int $domainId): array
    {
        return $this->heurekaReviewRepository->getLatestReviews($domainId);
    }
}
