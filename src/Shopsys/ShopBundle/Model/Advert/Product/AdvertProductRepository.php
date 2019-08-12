<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Advert\Product;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Shopsys\ShopBundle\Model\Advert\Advert;

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
     * @param \Shopsys\ShopBundle\Model\Advert\Advert $advert
     * @return \Shopsys\ShopBundle\Model\Advert\Product\AdvertProduct[]
     */
    public function getAdvertProductsByAdvert(Advert $advert): array
    {
        return $this->getAdvertProductRepository()->findBy(['advert' => $advert], ['position' => 'asc']);
    }
}
