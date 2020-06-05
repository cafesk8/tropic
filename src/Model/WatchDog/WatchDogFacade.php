<?php

declare(strict_types=1);

namespace App\Model\WatchDog;

use Doctrine\ORM\EntityManagerInterface;

class WatchDogFacade
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
     * @param \App\Model\WatchDog\WatchDogData $watchDogData
     * @return \App\Model\WatchDog\WatchDog
     */
    public function create(WatchDogData $watchDogData): WatchDog
    {
        $watchDog = new WatchDog($watchDogData);
        $this->em->persist($watchDog);
        $this->em->flush();

        return $watchDog;
    }
}
