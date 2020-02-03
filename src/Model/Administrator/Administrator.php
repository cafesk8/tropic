<?php

declare(strict_types=1);

namespace App\Model\Administrator;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Model\Administrator\Administrator as BaseAdministrator;
use Shopsys\FrameworkBundle\Model\Administrator\AdministratorData as BaseAdministratorData;

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
     * @var string[]
     *
     * @ORM\Column(type="json", nullable=false)
     */
    private $roles;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lastTransferIssuesVisit;

    /**
     * @param \App\Model\Administrator\AdministratorData $administratorData
     */
    public function __construct(BaseAdministratorData $administratorData)
    {
        parent::__construct($administratorData);
        $this->roles = $administratorData->roles;
    }

    /**
     * @param \App\Model\Administrator\AdministratorData $administratorData
     */
    public function edit(
        BaseAdministratorData $administratorData
    ): void {
        parent::edit($administratorData);
        $this->roles = $administratorData->roles;
    }

    /**
     * @inheritDoc
     */
    public function getRoles()
    {
        return array_merge(parent::getRoles(), $this->roles);
    }

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
