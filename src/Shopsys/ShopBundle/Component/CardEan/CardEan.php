<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\CardEan;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="card_eans")
 * @ORM\Entity
 */
class CardEan
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=13, unique=true, nullable=false)
     */
    protected $ean;

    /**
     * @param string $ean
     */
    public function __construct(string $ean)
    {
        $this->ean = $ean;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getEan(): string
    {
        return $this->ean;
    }

}
