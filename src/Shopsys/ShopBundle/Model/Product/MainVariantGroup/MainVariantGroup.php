<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\MainVariantGroup;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter;

/**
 * @ORM\Table(
 *     name="product_main_variant_groups",
 * )
 * @ORM\Entity
 */
class MainVariantGroup
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter|null
     *
     * @ORM\ManyToOne(targetEntity="\Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter")
     * @ORM\JoinColumn(nullable=true)
     */
    private $distinguishingParameter;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter $distinguishingParameter
     */
    public function __construct(Parameter $distinguishingParameter)
    {
        $this->distinguishingParameter = $distinguishingParameter;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter|null
     */
    public function getDistinguishingParameter(): ?Parameter
    {
        return $this->distinguishingParameter;
    }
}
