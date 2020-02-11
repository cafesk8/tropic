<?php

declare(strict_types=1);

namespace App\Model\Advert;

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
     * @var \App\Model\Category\Category[]
     *
     * @ORM\OneToMany(targetEntity="App\Model\Category\Category", mappedBy="advert")
     */
    private $categories;

    /**
     * @param \App\Model\Advert\AdvertData $advertData
     */
    public function __construct(BaseAdvertData $advertData)
    {
        parent::__construct($advertData);

        $this->smallTitle = $advertData->smallTitle;
        $this->bigTitle = $advertData->bigTitle;
        $this->productTitle = $advertData->productTitle;
        $this->categories = $advertData->categories;
    }

    /**
     * @param \App\Model\Advert\AdvertData $advertData
     */
    public function edit(BaseAdvertData $advertData)
    {
        parent::edit($advertData);

        $this->smallTitle = $advertData->smallTitle;
        $this->bigTitle = $advertData->bigTitle;
        $this->productTitle = $advertData->productTitle;
        $this->categories = $advertData->categories;
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

    /**
     * @return \App\Model\Category\Category[]
     */
    public function getCategories(): array
    {
        return $this->categories;
    }

    /**
     * @param \App\Model\Category\Category[] $categories
     */
    public function setCategories(array $categories): void
    {
        $this->categories = $categories;
    }
}
