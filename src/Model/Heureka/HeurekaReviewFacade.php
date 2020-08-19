<?php

declare(strict_types=1);

namespace App\Model\Heureka;

use Doctrine\ORM\EntityManagerInterface;
use SimpleXMLElement;

class HeurekaReviewFacade
{
    /**
     * @var \App\Model\Heureka\HeurekaReviewItemFactory
     */
    protected $heurekaReviewItemFactory;

    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    protected $em;

    /**
     * @var \App\Model\Heureka\HeurekaReviewRepository
     */
    protected $heurekaReviewRepository;

    /**
     * @var \App\Model\Heureka\HeurekaReviewFactory
     */
    protected $heurekaReviewFactory;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \App\Model\Heureka\HeurekaReviewItemFactory $heurekaReviewItemFactory
     * @param \App\Model\Heureka\HeurekaReviewRepository $heurekaReviewRepository
     * @param \App\Model\Heureka\HeurekaReviewFactory $heurekaReviewFactory
     */
    public function __construct(
        EntityManagerInterface $em,
        HeurekaReviewItemFactory $heurekaReviewItemFactory,
        HeurekaReviewRepository $heurekaReviewRepository,
        HeurekaReviewFactory $heurekaReviewFactory
    ){
        $this->em = $em;
        $this->heurekaReviewItemFactory = $heurekaReviewItemFactory;
        $this->heurekaReviewRepository = $heurekaReviewRepository;
        $this->heurekaReviewFactory = $heurekaReviewFactory;
    }

    /**
     * @param \SimpleXMLElement $itemFromXml
     * @return \App\Model\Heureka\HeurekaReview
     */
    public function create(SimpleXMLElement $itemFromXml): HeurekaReview
    {
        $heurekaReviewItem = $this->heurekaReviewItemFactory->create($itemFromXml);
        $heurekaReview = $this->heurekaReviewFactory->create($heurekaReviewItem);

        $this->em->persist($heurekaReview);
        $this->em->flush();

        return $heurekaReview;
    }

    /**
     * @return \App\Model\Heureka\HeurekaReview[]
     */
    public function getAll(): array
    {
        return $this->heurekaReviewRepository->getAll();
    }

    /**
     * @return \App\Model\Heureka\HeurekaReview[]
     */
    public function getLatestReviews(): array
    {
        return $this->heurekaReviewRepository->getLatestReviews();
    }
}
