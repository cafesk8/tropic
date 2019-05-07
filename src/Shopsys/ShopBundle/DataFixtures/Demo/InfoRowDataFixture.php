<?php

declare(strict_types = 1);

namespace Shopsys\ShopBundle\DataFixtures\Demo;

use Doctrine\Common\Persistence\ObjectManager;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;
use Shopsys\ShopBundle\Component\InfoRow\InfoRowFacade;

class InfoRowDataFixture extends AbstractReferenceFixture
{
    /**
     * @var \Shopsys\ShopBundle\Component\InfoRow\InfoRowFacade
     */
    private $infoRowFacade;

    /**
     * @param \Shopsys\ShopBundle\Component\InfoRow\InfoRowFacade $infoRowFacade
     */
    public function __construct(
        InfoRowFacade $infoRowFacade
    ) {
        $this->infoRowFacade = $infoRowFacade;
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $this->infoRowFacade->setInfoRow(
            true,
            'Upozornenie pre našich zákazníkov, táto verzia e-shopu obsahuje testovacie dáta.',
            2
        );
    }
}
