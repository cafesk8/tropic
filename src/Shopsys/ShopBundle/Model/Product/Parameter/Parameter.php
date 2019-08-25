<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Parameter;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter as BaseParameter;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterData;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterTranslation;

/**
 * @ORM\Table(name="parameters")
 * @ORM\Entity
 *
 * @method ParameterTranslation translation(?string $locale = null)
 */
class Parameter extends BaseParameter
{
    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $visibleOnFrontend;

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Parameter\ParameterData $parameterData
     */
    public function __construct(ParameterData $parameterData)
    {
        parent::__construct($parameterData);

        $this->visibleOnFrontend = $parameterData->visibleOnFrontend;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Parameter\ParameterData $parameterData
     */
    public function edit(ParameterData $parameterData)
    {
        parent::edit($parameterData);

        $this->visibleOnFrontend = $parameterData->visibleOnFrontend;
    }

    /**
     * @return bool
     */
    public function isVisibleOnFrontend(): bool
    {
        return $this->visibleOnFrontend;
    }
}
