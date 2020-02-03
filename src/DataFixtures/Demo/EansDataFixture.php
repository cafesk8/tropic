<?php

declare(strict_types=1);

namespace App\DataFixtures\Demo;

use Doctrine\Common\Persistence\ObjectManager;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;
use App\Component\CardEan\CardEanFacade;

class EansDataFixture extends AbstractReferenceFixture
{
    private const NUMBER_OF_EANS_TO_GENERATE = 25;

    /**
     * @var \App\Component\CardEan\CardEanFacade
     */
    private $cardEanFacade;

    /**
     * @param \App\Component\CardEan\CardEanFacade $cardEanFacade
     */
    public function __construct(CardEanFacade $cardEanFacade)
    {
        $this->cardEanFacade = $cardEanFacade;
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < self::NUMBER_OF_EANS_TO_GENERATE; $i++) {
            $this->cardEanFacade->createUniqueCardEan();
        }
    }
}
