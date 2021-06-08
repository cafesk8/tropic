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
    public const TYPE_MOBILE = 'mobile';

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
     * @var \App\Model\Category\CategoryDomain[]
     *
     * @ORM\OneToMany(targetEntity="App\Model\Category\CategoryDomain", mappedBy="advert")
     */
    private $categoryDomains;

    /**
     * @param \App\Model\Advert\AdvertData $advertData
     */
    public function __construct(BaseAdvertData $advertData)
    {
        parent::__construct($advertData);

        $this->smallTitle = $advertData->smallTitle;
        $this->bigTitle = $advertData->bigTitle;
        $this->productTitle = $advertData->productTitle;
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
        $categories = [];

        foreach ($this->categoryDomains as $categoryDomain) {
            if ($categoryDomain->getDomainId() === $this->getDomainId()) {
                $categories[] = $categoryDomain->getCategory();
            }
        }

        return $categories;
    }

    /**
     * @param \App\Model\Category\CategoryDomain[] $categoryDomains
     */
    public function setCategoryDomains(array $categoryDomains): void
    {
        $this->categoryDomains = $categoryDomains;
    }
}
