<?php

declare(strict_types=1);

namespace App\Model\Product\Bestseller;

use Doctrine\ORM\EntityManagerInterface;

class BestsellerFacade
{
    private EntityManagerInterface $em;

    private BestsellerRepository $bestsellerRepository;

    private BestsellerFactory $bestsellerFactory;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @param \App\Model\Product\Bestseller\BestsellerRepository $bestsellerRepository
     * @param \App\Model\Product\Bestseller\BestsellerFactory $bestsellerFactory
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        BestsellerRepository $bestsellerRepository,
        BestsellerFactory $bestsellerFactory
    ) {
        $this->em = $entityManager;
        $this->bestsellerRepository = $bestsellerRepository;
        $this->bestsellerFactory = $bestsellerFactory;
    }

    /**
     * @param int $domainId
     * @return \App\Model\Product\Bestseller\Bestseller[]
     */
    public function getAllByDomainId(int $domainId): array
    {
        return $this->bestsellerRepository->getAllByDomainId($domainId);
    }

    /**
     * @param int $domainId
     * @param \App\Model\Product\Product[] $products
     */
    public function saveBestsellerForDomain(int $domainId, array $products)
    {
        $oldBestsellers = $this->bestsellerRepository->getAllByDomainId($domainId);
        foreach ($oldBestsellers as $oldBestseller) {
            $this->em->remove($oldBestseller);
        }
        $this->em->flush();

        $position = 1;
        foreach ($products as $product) {
            $bestseller = $this->bestsellerFactory->create($product, $domainId, $position++);
            $this->em->persist($bestseller);
        }
        $this->em->flush();
    }

    /**
     * @param int $domainId
     * @return int[]
     */
    public function getProductPositionIndexedById(int $domainId): array
    {
        $idsIndexedByPosition = [];

        foreach ($this->bestsellerRepository->getProductIdsAndPosition($domainId) as $bestsellerArray) {
            $idsIndexedByPosition[$bestsellerArray['productId']] = $bestsellerArray['position'];
        }

        return $idsIndexedByPosition;
    }
}