<?php

declare(strict_types=1);

namespace App\Component\CardEan;

use App\Component\CardEan\Exception\CardEanCouldNotBeSetToUserException;
use App\Component\CardEan\Exception\ReachMaxCardEanUniqueResolveAttemptException;
use App\Model\Customer\User\CustomerUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUser;

class CardEanFacade
{
    private const MAX_URL_UNIQUE_RESOLVE_ATTEMPT = 1000;
    private const MAX_SET_EAN_TO_USER_ATTEMPTS = 10;

    /**
     * @var \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator
     */
    private $em;

    /**
     * @var \App\Component\CardEan\CardEanGenerator
     */
    private $cardEanGenerator;

    /**
     * @var \App\Component\CardEan\CardEanRepository
     */
    private $cardEanRepository;

    /**
     * @var \App\Model\Customer\User\CustomerUserRepository
     */
    private $customerUserRepository;

    /**
     * @param \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator $em
     * @param \App\Component\CardEan\CardEanGenerator $cardEanGenerator
     * @param \App\Component\CardEan\CardEanRepository $cardEanRepository
     * @param \App\Model\Customer\User\CustomerUserRepository $customerUserRepository
     */
    public function __construct(
        EntityManagerInterface $em,
        CardEanGenerator $cardEanGenerator,
        CardEanRepository $cardEanRepository,
        CustomerUserRepository $customerUserRepository
    ) {
        $this->em = $em;
        $this->cardEanGenerator = $cardEanGenerator;
        $this->cardEanRepository = $cardEanRepository;
        $this->customerUserRepository = $customerUserRepository;
    }

    /**
     * @return \App\Component\CardEan\CardEan
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
            $eanUsed = $this->customerUserRepository->eanUsed($newEan);

            $isNewEanUnique = $eanExists === false && $eanUsed === false;
        } while ($isNewEanUnique === false);

        return $this->create($newEan);
    }

    /**
     * @param string $ean
     * @return \App\Component\CardEan\CardEan
     */
    public function create(string $ean): CardEan
    {
        $cardEan = new CardEan($ean);

        $this->em->persist($cardEan);
        $this->em->flush($cardEan);

        return $cardEan;
    }

    /**
     * @param \App\Model\Customer\User\CustomerUser $customerUser
     */
    public function addPrereneratedEanToUserAndFlush(CustomerUser $customerUser)
    {
        $attempt = 0;
        $eanIsSetToUser = false;
        $exception = null;

        do {
            try {
                $this->em->beginTransaction();

                $cardEan = $this->cardEanRepository->getOnePregeneratedEan();
                $customerUser->setEan($cardEan->getEan());

                $this->em->remove($cardEan);
                $this->em->flush([$cardEan, $customerUser]);

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
