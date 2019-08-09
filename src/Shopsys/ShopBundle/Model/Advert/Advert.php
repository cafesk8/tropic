<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Advert;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Model\Advert\Advert as BaseAdvert;
use Shopsys\FrameworkBundle\Model\Advert\AdvertData as BaseAdvertData;

/**
 * @ORM\Entity
 * @ORM\Table(name="adverts")
 */
class Advert extends BaseAdvert
{
    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $smallTitle;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $bigTitle;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $productTitle;

    /**
     * @param \Shopsys\ShopBundle\Model\Advert\AdvertData $advertData
     */
    public function __construct(BaseAdvertData $advertData)
    {
        parent::__construct($advertData);

        $this->smallTitle = $advertData->smallTitle;
        $this->bigTitle = $advertData->bigTitle;
        $this->productTitle = $advertData->productTitle;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Advert\AdvertData $advertData
     */
    public function edit(BaseAdvertData $advertData)
    {
        parent::edit($advertData);

        $this->smallTitle = $advertData->smallTitle;
        $this->bigTitle = $advertData->bigTitle;
        $this->productTitle = $advertData->productTitle;
    }

    /**
     * @return string|null
     */
    public function getBigTitle(): ?string
    {
        return $this->bigTitle;
    }

    /**
     * @return string|null
     */
    public function getSmallTitle(): ?string
    {
        return $this->smallTitle;
    }

    /**
     * @return string|null
     */
    public function getProductTitle(): ?string
    {
        return $this->productTitle;
    }
}
