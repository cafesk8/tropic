<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\CardEan;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Shopsys\FrameworkBundle\Model\Customer\User;
use Shopsys\ShopBundle\Component\CardEan\Exception\CardEanCouldNotBeSetToUserException;
use Shopsys\ShopBundle\Component\CardEan\Exception\ReachMaxCardEanUniqueResolveAttemptException;
use Shopsys\ShopBundle\Model\Customer\UserRepository;

class CardEanFacade
{
    private const MAX_URL_UNIQUE_RESOLVE_ATTEMPT = 1000;
    private const MAX_SET_EAN_TO_USER_ATTEMPTS = 10;

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

    /**
     * @param \Shopsys\ShopBundle\Model\Customer\User $user
     */
    public function addPrereneratedEanToUserAndFlush(User $user)
    {
        $attempt = 0;
        $eanIsSetToUser = false;
        $exception = null;

        do {
            try {
                $this->em->beginTransaction();

                $cardEan = $this->cardEanRepository->getOnePregeneratedEan();
                $user->setEan($cardEan->getEan());

                $this->em->remove($cardEan);
                $this->em->flush([$cardEan, $user]);

                $this->em->commit();

                $eanIsSetToUser = true;
            } catch (Exception $exception) {
                $this->em->rollback();
                $attempt++;
            }
        } while ($eanIsSetToUser === false && $attempt < self::MAX_SET_EAN_TO_USER_ATTEMPTS);

        if ($eanIsSetToUser === false) {
            $exceptionMessage = '';
            if ($exception !== null) {
                $exceptionMessage = get_class($exception) . ': ' . $exception->getMessage();
            }

            throw new CardEanCouldNotBeSetToUserException($exceptionMessage);
        }
    }
}
