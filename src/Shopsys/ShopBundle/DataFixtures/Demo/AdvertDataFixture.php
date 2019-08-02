<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\DataFixtures\Demo;

use Doctrine\Common\Persistence\ObjectManager;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;
use Shopsys\FrameworkBundle\Model\Advert\Advert;
use Shopsys\FrameworkBundle\Model\Advert\AdvertDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Advert\AdvertFacade;

class AdvertDataFixture extends AbstractReferenceFixture
{
    public const ADVERT_FIRST_SQUARE = 'advert_first_square';
    public const ADVERT_SECOND_SQUARE = 'advert_second_square';
    public const ADVERT_THIRD_SQUARE = 'advert_third_square';
    public const ADVERT_FOURTH_SQUARE = 'advert_fourth_square';
    public const ADVERT_FIFTH_RECTANGLE = 'advert_fifth_rectangle';

    /**
     * @var \Shopsys\FrameworkBundle\Model\Advert\AdvertFacade
     */
    protected $advertFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Advert\AdvertDataFactoryInterface
     */
    protected $advertDataFactory;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Advert\AdvertFacade $advertFacade
     * @param \Shopsys\FrameworkBundle\Model\Advert\AdvertDataFactoryInterface $advertDataFactory
     */
    public function __construct(AdvertFacade $advertFacade, AdvertDataFactoryInterface $advertDataFactory)
    {
        $this->advertFacade = $advertFacade;
        $this->advertDataFactory = $advertDataFactory;
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $advertData = $this->advertDataFactory->create();
        $advertData->domainId = 1;
        $advertData->name = 'Pro pořádné chlapy';
        $advertData->type = Advert::TYPE_IMAGE;
        $advertData->hidden = false;
        $advertData->positionName = 'firstSquare';
        $advertData->link = '/admin';
        $advert = $this->advertFacade->create($advertData);
        $this->addReference(self::ADVERT_FIRST_SQUARE, $advert);

        $advertData->domainId = 1;
        $advertData->name = 'Kempování';
        $advertData->type = Advert::TYPE_IMAGE;
        $advertData->hidden = false;
        $advertData->positionName = 'secondSquare';
        $advertData->link = '/admin';
        $advert = $this->advertFacade->create($advertData);
        $this->addReference(self::ADVERT_SECOND_SQUARE, $advert);

        $advertData->domainId = 1;
        $advertData->name = 'Hvězda zahradnictví';
        $advertData->type = Advert::TYPE_IMAGE;
        $advertData->hidden = false;
        $advertData->positionName = 'thirdSquare';
        $advertData->link = '/admin';
        $advert = $this->advertFacade->create($advertData);
        $this->addReference(self::ADVERT_THIRD_SQUARE, $advert);

        $advertData->domainId = 1;
        $advertData->name = 'Pro pořádné chlapy';
        $advertData->type = Advert::TYPE_IMAGE;
        $advertData->hidden = false;
        $advertData->positionName = 'fourthSquare';
        $advertData->link = '/admin';
        $advert = $this->advertFacade->create($advertData);
        $this->addReference(self::ADVERT_FOURTH_SQUARE, $advert);

        $advertData->domainId = 1;
        $advertData->name = 'Šití na míru';
        $advertData->type = Advert::TYPE_IMAGE;
        $advertData->hidden = false;
        $advertData->positionName = 'fifthRectangle';
        $advertData->link = '/admin';
        $advert = $this->advertFacade->create($advertData);
        $this->addReference(self::ADVERT_FIFTH_RECTANGLE, $advert);
    }
}
