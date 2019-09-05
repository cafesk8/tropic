<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\CardEan;

use Doctrine\ORM\EntityManagerInterface;

class CardEanFacade
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;

    /**
     * @var \Shopsys\ShopBundle\Component\CardEan\CardEanGenerator
     */
    private $cardEanGenerator;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\ShopBundle\Component\CardEan\CardEanGenerator $cardEanGenerator
     */
    public function __construct(
        EntityManagerInterface $em,
        CardEanGenerator $cardEanGenerator
    ) {
        $this->em = $em;
        $this->cardEanGenerator = $cardEanGenerator;
    }

    /**
     * @return \Shopsys\ShopBundle\Component\CardEan\CardEan
     */
    public function createUniqueCardEan(): CardEan
    {
        $ean = $this->cardEanGenerator->generate();
        return $this->create();
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
