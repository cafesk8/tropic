<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Brand;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Model\Product\Brand\Brand as BaseBrand;
use Shopsys\FrameworkBundle\Model\Product\Brand\BrandData as BaseBrandData;
use Shopsys\ShopBundle\Model\Product\Brand\Exception\InvalidBrandTypeException;

/**
 * @ORM\Table(name="brands")
 * @ORM\Entity
 */
class Brand extends BaseBrand
{
    public const TYPE_DEFAULT = 'default';
    public const TYPE_MAIN_BUSHMAN = 'mainBushman';

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=50, nullable=false)
     */
    private $type;

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Brand\BrandData $brandData
     */
    public function __construct(BaseBrandData $brandData)
    {
        parent::__construct($brandData);
        $this->setType($brandData->type);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Brand\BrandData $brandData
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
        if (in_array($type, [self::TYPE_DEFAULT, self::TYPE_MAIN_BUSHMAN], true) === false) {
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
}
