<?php

declare(strict_types=1);

namespace App\Model\Advert\Product;

use App\Model\Advert\Advert;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class AdvertProductRepository
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
    private function getAdvertProductRepository(): EntityRepository
    {
        return $this->em->getRepository(AdvertProduct::class);
    }

    /**
     * @param \App\Model\Advert\Advert $advert
     * @return \App\Model\Advert\Product\AdvertProduct[]
     */
    public function getAdvertProductsByAdvert(Advert $advert): array
    {
        return $this->getAdvertProductRepository()->findBy(['advert' => $advert], ['position' => 'asc']);
    }
}
