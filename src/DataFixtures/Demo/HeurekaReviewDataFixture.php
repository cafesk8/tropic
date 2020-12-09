<?php

declare(strict_types=1);

namespace App\DataFixtures\Demo;

use App\Component\Domain\DomainHelper;
use App\Model\Heureka\HeurekaReviewFacade;
use App\Model\Heureka\HeurekaReviewItemFactory;
use DateTime;
use Doctrine\Persistence\ObjectManager;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;

class HeurekaReviewDataFixture extends AbstractReferenceFixture
{
    protected HeurekaReviewItemFactory $heurekaReviewItemFactory;

    protected HeurekaReviewFacade $heurekaReviewFacade;

    /**
     * @param \App\Model\Heureka\HeurekaReviewItemFactory $heurekaReviewItemFactory
     * @param \App\Model\Heureka\HeurekaReviewFacade $heurekaReviewFacade
     */
    public function __construct(HeurekaReviewItemFactory $heurekaReviewItemFactory, HeurekaReviewFacade $heurekaReviewFacade) {
        $this->heurekaReviewItemFactory = $heurekaReviewItemFactory;
        $this->heurekaReviewFacade = $heurekaReviewFacade;
    }

    /**
     * @param \Doctrine\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $heurekaReviewItem = $this->heurekaReviewItemFactory->createManually(
            DomainHelper::CZECH_DOMAIN,
            000001,
            new DateTime('02 Jan 2020'),
            5,
            'Viktor Sládek',
            'OK
            Velký výběr zboží
            Rychlé dodání',
            null,
            'Duis condimentum augue id magna semper rutrum. Fusce dui leo, imperdiet in, aliquam sit amet.'
        );
        $this->heurekaReviewFacade->create($heurekaReviewItem);

        $heurekaReviewItem = $this->heurekaReviewItemFactory->createManually(
            DomainHelper::CZECH_DOMAIN,
            000002,
            new DateTime('01 Jan 2020'),
            4,
            'Vratislav Čermák',
            'Výběr zboží',
            'Quam vel velit
            Enim ipsum id lacus',
            'Praesent vitae arcu tempor neque lacinia pretium. Morbi scelerisque luctus velit.'
        );
        $this->heurekaReviewFacade->create($heurekaReviewItem);

        $heurekaReviewItem = $this->heurekaReviewItemFactory->createManually(
            DomainHelper::SLOVAK_DOMAIN,
            000003,
            new DateTime('09 Jan 2020'),
            3,
            'Marek Holič',
            null,
            'Quam vel velit
            Tempor neque',
            'Praesent vitae arcu tempor neque lacinia pretium. Morbi scelerisque luctus velit.'
        );
        $this->heurekaReviewFacade->create($heurekaReviewItem);

        $heurekaReviewItem = $this->heurekaReviewItemFactory->createManually(
            DomainHelper::SLOVAK_DOMAIN,
            000004,
            new DateTime('05 Jan 2020'),
            4.5,
            'Branislav Nedved',
            'Nulla volutpat purus
            Sed neque mollis
            Ditum amet',
            null,
            'Cras eget rutrum quam. Pellentesque ut lorem sit amet neque vestibulum malesuada nec vitae ante.'
        );
        $this->heurekaReviewFacade->create($heurekaReviewItem);
    }
}
