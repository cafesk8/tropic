<?php

declare(strict_types=1);

namespace App\Model\Product\Brand;

use App\Model\Product\Brand\Exception\BrandDeletionForbiddenException;
use App\Model\Product\Brand\Exception\InvalidBrandTypeException;
use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Model\Product\Brand\Brand as BaseBrand;
use Shopsys\FrameworkBundle\Model\Product\Brand\BrandData as BaseBrandData;

/**
 * @ORM\Table(name="brands")
 * @ORM\Entity
 * @method setTranslations(\App\Model\Product\Brand\BrandData $brandData)
 * @method setDomains(\App\Model\Product\Brand\BrandData $brandData)
 * @method createDomains(\App\Model\Product\Brand\BrandData $brandData)
 */
class Brand extends BaseBrand
{
    public const TYPE_DEFAULT = 'default';
    public const TYPE_MAIN_SHOPSYS = 'mainShopsys';

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=50, nullable=false)
     */
    private $type;

    /**
     * @param \App\Model\Product\Brand\BrandData $brandData
     */
    public function __construct(BaseBrandData $brandData)
    {
        parent::__construct($brandData);
        $this->setType($brandData->type);
    }

    /**
     * @param \App\Model\Product\Brand\BrandData $brandData
     */
    public function edit(BaseBrandData $brandData)
    {
        parent::edit($brandData);
        $this->setType($brandData->type);
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        if (in_array($type, [self::TYPE_DEFAULT, self::TYPE_MAIN_SHOPSYS], true) === false) {
            throw new InvalidBrandTypeException(sprintf('Invalid brand type `%s`', $type));
        }
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    public function checkForDelete(): void
    {
        if ($this->type === self::TYPE_MAIN_SHOPSYS) {
            throw new BrandDeletionForbiddenException(sprintf('Brand with id `%s` deletion is forbidden', $this->id));
        }
    }
}
