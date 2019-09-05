<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\CardEan;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\ShopBundle\Component\CardEan\Exception\ReachMaxCardEanUniqueResolveAttemptException;
use Shopsys\ShopBundle\Model\Customer\UserRepository;

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
     * @var \Shopsys\ShopBundle\Model\Customer\UserRepository
     */
    private $userRepository;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\ShopBundle\Component\CardEan\CardEanGenerator $cardEanGenerator
     * @param \Shopsys\ShopBundle\Component\CardEan\CardEanRepository $cardEanRepository
     * @param \Shopsys\ShopBundle\Model\Customer\UserRepository $userRepository
     */
    public function __construct(
        EntityManagerInterface $em,
        CardEanGenerator $cardEanGenerator,
        CardEanRepository $cardEanRepository,
        UserRepository $userRepository
    ) {
        $this->em = $em;
        $this->cardEanGenerator = $cardEanGenerator;
        $this->cardEanRepository = $cardEanRepository;
        $this->userRepository = $userRepository;
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
            $eanUsed = $this->userRepository->eanUsed($newEan);

            $isNewEanUnique = $eanExists === false && $eanUsed === false;
        } while ($isNewEanUnique === false);

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
