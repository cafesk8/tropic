<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\CardEan;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Shopsys\ShopBundle\Component\CardEan\Exception\NotFreeCardEansException;

class CardEanRepository
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;

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
    private function getCardEanRepository(): EntityRepository
    {
        return $this->em->getRepository(CardEan::class);
    }

    /**
     * @param string $ean
     * @return \Shopsys\ShopBundle\Component\CardEan\CardEan|null
     */
    private function findByEan(string $ean): ?CardEan
    {
        return $this->getCardEanRepository()->findOneBy(['ean' => $ean]);
    }

    /**
     * @param string $ean
     * @return bool
     */
    public function eanExists(string $ean): bool
    {
        $ean = $this->findByEan($ean);

        $exists = false;
        if ($ean !== null) {
            $exists = true;
        }

        return $exists;
    }

    /**
     * @return \Shopsys\ShopBundle\Component\CardEan\CardEan
     */
    public function getOnePregeneratedEan(): CardEan
    {
        $cardEan = $this->getCardEanRepository()->findOneBy([]);

        if ($cardEan === null) {
            throw new NotFreeCardEansException();
        }

        return $cardEan;
    }
}
