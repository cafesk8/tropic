<?php

declare(strict_types=1);

namespace App\Model\Product\Brand;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Component\String\TransformString;
use Shopsys\FrameworkBundle\Model\Product\Brand\Brand as BaseBrand;
use Shopsys\FrameworkBundle\Model\Product\Brand\BrandData as BaseBrandData;

/**
 * @ORM\Table(name="brands")
 * @ORM\Entity
 */
class Brand extends BaseBrand
{
    /**
     * @ORM\Column(type="string", nullable=false)
     */
    private string $slug;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Brand\BrandData $brandData
     */
    public function __construct(BaseBrandData $brandData)
    {
        parent::__construct($brandData);
        $this->fillCommonProperties($brandData);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Brand\BrandData $brandData
     */
    public function edit(BaseBrandData $brandData)
    {
        parent::edit($brandData);
        $this->fillCommonProperties($brandData);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Brand\BrandData $brandData
     */
    private function fillCommonProperties(BaseBrandData $brandData): void
    {
        $this->slug = TransformString::stringToFriendlyUrlSlug($brandData->name);
    }

    /**
     * @return string
     */
    public function getSlug(): string
    {
        return $this->slug;
    }
}
