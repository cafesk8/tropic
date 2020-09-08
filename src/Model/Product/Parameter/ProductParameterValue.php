<?php

declare(strict_types=1);

namespace App\Model\Product\Parameter;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterValue;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValue as BaseProductParameterValue;
use Shopsys\FrameworkBundle\Model\Product\Product;

/**
 * @ORM\Table(name="product_parameter_values")
 * @ORM\Entity
 * @property \App\Model\Product\Product $product
 * @property \App\Model\Product\Parameter\Parameter $parameter
 * @property \App\Model\Product\Parameter\ParameterValue $value
 * @method \App\Model\Product\Product getProduct()
 * @method \App\Model\Product\Parameter\Parameter getParameter()
 * @method \App\Model\Product\Parameter\ParameterValue getValue()
 */
class ProductParameterValue extends BaseProductParameterValue
{
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $position = null;

    /**
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $takenFromMainVariant = false;

    /**
     * @param \App\Model\Product\Product $product
     * @param \App\Model\Product\Parameter\Parameter $parameter
     * @param \App\Model\Product\Parameter\ParameterValue $value
     * @param int|null $position
     * @param bool $takenFromMainVariant
     */
    public function __construct(
        Product $product,
        Parameter $parameter,
        ParameterValue $value,
        ?int $position = null,
        bool $takenFromMainVariant = false
    ) {
        $this->position = $position;
        $this->takenFromMainVariant = $takenFromMainVariant;
        parent::__construct($product, $parameter, $value);
    }

    /**
     * @return int|null
     */
    public function getPosition(): ?int
    {
        return $this->position;
    }

    /**
     * @return bool
     */
    public function isTakenFromMainVariant(): bool
    {
        return $this->takenFromMainVariant;
    }
}
