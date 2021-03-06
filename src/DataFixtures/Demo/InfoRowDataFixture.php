<?php

declare(strict_types=1);

namespace App\DataFixtures\Demo;

use App\Component\InfoRow\InfoRowFacade;
use Doctrine\Common\Persistence\ObjectManager;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;
use Shopsys\FrameworkBundle\Component\Domain\Domain;

class InfoRowDataFixture extends AbstractReferenceFixture
{
    /**
     * @var \App\Component\InfoRow\InfoRowFacade
     */
    private $infoRowFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @param \App\Component\InfoRow\InfoRowFacade $infoRowFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(
        InfoRowFacade $infoRowFacade,
        Domain $domain
    ) {
        $this->infoRowFacade = $infoRowFacade;
        $this->domain = $domain;
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->domain->getAll() as $domainConfig) {
            $this->infoRowFacade->setInfoRow(
                true,
                t('Upozornění pro naše zákazníky - tato verze e-shopu obsahuje testovací data.', [], 'dataFixtures', $domainConfig->getLocale()),
                $domainConfig->getId()
            );
        }
    }
}
