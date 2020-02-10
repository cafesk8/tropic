<?php

declare(strict_types=1);

namespace App\Model\Administrator;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Model\Administrator\Administrator as BaseAdministrator;

/**
 * @ORM\Entity
 * @ORM\Table(
 *   name="administrators",
 *   indexes={
 *     @ORM\Index(columns={"username"})
 *   }
 * )
 */
class Administrator extends BaseAdministrator
{
    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lastTransferIssuesVisit;

    /**
     * @return \DateTime|null
     */
    public function getLastTransferIssuesVisit(): ?DateTime
    {
        return $this->lastTransferIssuesVisit;
    }

    /**
     * @param \DateTime|null $lastTransferIssuesVisit
     */
    public function setLastTransferIssuesVisit(?DateTime $lastTransferIssuesVisit): void
    {
        $this->lastTransferIssuesVisit = $lastTransferIssuesVisit;
    }
}
