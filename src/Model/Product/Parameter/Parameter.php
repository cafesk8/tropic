<?php

declare(strict_types=1);

namespace App\Model\Product\Parameter;

use App\Model\Product\Parameter\Exception\InvalidParameterTypeException;
use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter as BaseParameter;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterData;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterTranslation;

/**
 * @ORM\Table(name="parameters")
 * @ORM\Entity
 *
 * @method ParameterTranslation translation(?string $locale = null)
 * @method setTranslations(\App\Model\Product\Parameter\ParameterData $parameterData)
 */
class Parameter extends BaseParameter
{
    public const TYPE_DEFAULT = 'default';
    public const TYPE_COLOR = 'color';
    public const TYPE_SIZE = 'size';

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=50, nullable=false)
     */
    private $type;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $visibleOnFrontend;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $mallId;

    /**
     * @param \App\Model\Product\Parameter\ParameterData $parameterData
     */
    public function __construct(ParameterData $parameterData)
    {
        parent::__construct($parameterData);

        $this->mallId = $parameterData->mallId;
        $this->setType($parameterData->type);
        $this->visibleOnFrontend = $parameterData->visibleOnFrontend;
    }

    /**
     * @param \App\Model\Product\Parameter\ParameterData $parameterData
     */
    public function edit(ParameterData $parameterData)
    {
        parent::edit($parameterData);

        $this->mallId = $parameterData->mallId;
        $this->setType($parameterData->type);
        $this->visibleOnFrontend = $parameterData->visibleOnFrontend;
    }

    /**
     * @return bool
     */
    public function isVisibleOnFrontend(): bool
    {
        return $this->visibleOnFrontend;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        if (in_array($type, [self::TYPE_DEFAULT, self::TYPE_COLOR, self::TYPE_SIZE], true) === false) {
            throw new InvalidParameterTypeException(sprintf('Invalid parameter type `%s`', $type));
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

    /**
     * @return string|null
     */
    public function getMallId(): ?string
    {
        return $this->mallId;
    }
}
