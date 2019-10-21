<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Administrator;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Model\Administrator\Administrator as BaseAdministrator;
use Shopsys\FrameworkBundle\Model\Administrator\AdministratorData as BaseAdministratorData;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

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
     * @param \Shopsys\ShopBundle\Model\Administrator\AdministratorData $administratorData
     */
    public function __construct(BaseAdministratorData $administratorData)
    {
        parent::__construct($administratorData);
        $this->roles = $administratorData->roles;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Administrator\AdministratorData $administratorData
     * @param \Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface $encoderFactory
     * @param \Shopsys\FrameworkBundle\Model\Administrator\Administrator|null $administratorByUserName
     */
    public function edit(
        BaseAdministratorData $administratorData,
        EncoderFactoryInterface $encoderFactory,
        ?BaseAdministrator $administratorByUserName
    ) {
        parent::edit($administratorData, $encoderFactory, $administratorByUserName);
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
