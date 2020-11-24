<?php

declare(strict_types=1);

namespace App\DataFixtures\Demo;

use App\Component\Domain\DomainHelper;
use App\Model\Heureka\HeurekaReviewFacade;
use App\Model\Heureka\HeurekaReviewItemFactory;
use Doctrine\Common\Persistence\ObjectManager;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;
use SimpleXMLElement;

class HeurekaReviewDataFixture extends AbstractReferenceFixture
{
    /**
     * @var \App\Model\Heureka\HeurekaReviewItemFactory
     */
    protected $heurekaReviewItemFactory;

    /**
     * @var \App\Model\Heureka\HeurekaReviewFacade
     */
    protected $heurekaReviewFacade;

    /**
     * @param \App\Model\Heureka\HeurekaReviewItemFactory $heurekaReviewItemFactory
     * @param \App\Model\Heureka\HeurekaReviewFacade $heurekaReviewFacade
     */
    public function __construct(
        HeurekaReviewItemFactory $heurekaReviewItemFactory,
        HeurekaReviewFacade $heurekaReviewFacade
    ) {
        $this->heurekaReviewItemFactory = $heurekaReviewItemFactory;
        $this->heurekaReviewFacade = $heurekaReviewFacade;
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $ratingXml = <<<XML
        <?xml version='1.0' standalone='yes'?>
        <review>
            <rating_id>00001</rating_id>
            <unix_timestamp>1579478400</unix_timestamp>
            <total_rating>5</total_rating>
            <name>Viktor Sládek</name>
            <pros>Ok
            Velký výběr yboží
            Rychlé dodání</pros>
            <summary>Duis condimentum augue id magna semper rutrum. Fusce dui leo, imperdiet in, aliquam sit amet.</summary>
        </review>
        XML;

        $ratingXml = new SimpleXMLElement($ratingXml);
        $ratingItem = $this->heurekaReviewItemFactory->create($ratingXml, DomainHelper::CZECH_DOMAIN);
        $this->heurekaReviewFacade->create($ratingItem);

        $ratingXml = <<<XML
        <?xml version='1.0' standalone='yes'?>
        <review>
            <rating_id>0002</rating_id>
            <unix_timestamp>1586044800</unix_timestamp>
            <total_rating>4</total_rating>
            <name>Vratislav Čermák</name>
            <pros>Výběr yboží</pros>
            <cons>Quam vel velit
            Enim ipsum id lacus</cons>
            <summary>Praesent vitae arcu tempor neque lacinia pretium. Morbi scelerisque luctus velit.</summary>
        </review>
        XML;

        $ratingXml = new SimpleXMLElement($ratingXml);
        $ratingItem = $this->heurekaReviewItemFactory->create($ratingXml, DomainHelper::CZECH_DOMAIN);
        $this->heurekaReviewFacade->create($ratingItem);

        $ratingXml = <<<XML
        <?xml version='1.0' standalone='yes'?>
        <review>
            <rating_id>0003</rating_id>
            <unix_timestamp>1579478400</unix_timestamp>
            <total_rating>3</total_rating>
            <name>Marek Holič</name>
            <pros>Morbi scelerisque
            Tempor neque</pros>
            <cons>Quam vel velit</cons>
            <summary>Praesent vitae arcu tempor neque lacinia pretium. Morbi scelerisque luctus velit.</summary>
        </review>
        XML;

        $ratingXml = new SimpleXMLElement($ratingXml);
        $ratingItem = $this->heurekaReviewItemFactory->create($ratingXml, DomainHelper::SLOVAK_DOMAIN);
        $this->heurekaReviewFacade->create($ratingItem);

        $ratingXml = <<<XML
        <?xml version='1.0' standalone='yes'?>
        <review>
            <rating_id>0004</rating_id>
            <unix_timestamp>1586044800</unix_timestamp>
            <total_rating>5</total_rating>
            <name>Branislav Nedved</name>
            <pros>Nulla volutpat purus
            Sed neque mollis
            Ditum amet</pros>
            <summary>Cras eget rutrum quam. Pellentesque ut lorem sit amet neque vestibulum malesuada nec vitae ante.</summary>
        </review>
        XML;

        $ratingXml = new SimpleXMLElement($ratingXml);
        $ratingItem = $this->heurekaReviewItemFactory->create($ratingXml, DomainHelper::SLOVAK_DOMAIN);
        $this->heurekaReviewFacade->create($ratingItem);
    }
}
