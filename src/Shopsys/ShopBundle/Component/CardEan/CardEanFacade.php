<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\CardEan;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\ShopBundle\Component\CardEan\Exception\ReachMaxCardEanUniqueResolveAttemptException;

class CardEanFacade
{
    private const MAX_URL_UNIQUE_RESOLVE_ATTEMPT = 1000;

    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;

    /**
     * @var \Shopsys\ShopBundle\Component\CardEan\CardEanGenerator
     */
    private $cardEanGenerator;

    /**
     * @var \Shopsys\ShopBundle\Component\CardEan\CardEanRepository
     */
    private $cardEanRepository;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\ShopBundle\Component\CardEan\CardEanGenerator $cardEanGenerator
     * @param \Shopsys\ShopBundle\Component\CardEan\CardEanRepository $cardEanRepository
     */
    public function __construct(
        EntityManagerInterface $em,
        CardEanGenerator $cardEanGenerator,
        CardEanRepository $cardEanRepository
    ) {
        $this->em = $em;
        $this->cardEanGenerator = $cardEanGenerator;
        $this->cardEanRepository = $cardEanRepository;
    }

    /**
     * @return \Shopsys\ShopBundle\Component\CardEan\CardEan
     */
    public function createUniqueCardEan(): CardEan
    {
        $attempt = 0;
        $newEan = null;
        do {
            $attempt++;

            if ($attempt > self::MAX_URL_UNIQUE_RESOLVE_ATTEMPT) {
                throw new ReachMaxCardEanUniqueResolveAttemptException(sprintf(
                    'Max card ean unique resolve attempt `%d` has been reached',
                    $attempt
                ));
            }

            $newEan = $this->cardEanGenerator->generate();
            $eanExists = $this->cardEanRepository->eanExists($newEan);
        } while ($eanExists === true);

        return $this->create($newEan);
    }

    /**
     * @param string $ean
     * @return \Shopsys\ShopBundle\Component\CardEan\CardEan
     */
    public function create(string $ean): CardEan
    {
        $cardEan = new CardEan($ean);

        $this->em->persist($cardEan);
        $this->em->flush($cardEan);

        return $cardEan;
    }
}
