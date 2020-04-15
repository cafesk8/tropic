<?php

declare(strict_types=1);

namespace App\Model\Pricing\Vat;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Model\Pricing\Vat\Vat as BaseVat;
use Shopsys\FrameworkBundle\Model\Pricing\Vat\VatData as BaseVatData;

/**
 * @ORM\Table(name="vats")
 * @ORM\Entity
 * @property \App\Model\Pricing\Vat\Vat|null $replaceWith
 * @method \App\Model\Pricing\Vat\Vat|null getReplaceWith()
 * @method markForDeletion(\App\Model\Pricing\Vat\Vat $newVat)
 */
class Vat extends BaseVat
{
    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $pohodaId;

    /**
     * @param \App\Model\Pricing\Vat\VatData $vatData
     * @param int $domainId
     */
    public function __construct(BaseVatData $vatData, int $domainId)
    {
        parent::__construct($vatData, $domainId);
        $this->pohodaId = $vatData->pohodaId;
    }

    /**
     * @param \App\Model\Pricing\Vat\VatData $vatData
     */
    public function edit(BaseVatData $vatData)
    {
        parent::edit($vatData);
        $this->pohodaId = $vatData->pohodaId;
    }

    /**
     * @return int|null
     */
    public function getPohodaId(): ?int
    {
        return $this->pohodaId;
    }
}
