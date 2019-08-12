<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\DataFixtures\Demo;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;
use Shopsys\FrameworkBundle\Model\Advert\Advert;
use Shopsys\FrameworkBundle\Model\Advert\AdvertFacade;
use Shopsys\ShopBundle\Model\Advert\AdvertDataFactory;

class AdvertDataFixture extends AbstractReferenceFixture implements DependentFixtureInterface
{
    public const ADVERT_FIRST_SQUARE = 'advert_first_square';
    public const ADVERT_SECOND_SQUARE = 'advert_second_square';
    public const ADVERT_THIRD_SQUARE = 'advert_third_square';
    public const ADVERT_FOURTH_SQUARE = 'advert_fourth_square';
    public const ADVERT_FIFTH_RECTANGLE = 'advert_fifth_rectangle';
    public const ADVERT_SIXTH_RECTANGLE = 'advert_sixth_rectangle';

    /**
     * @var \Shopsys\FrameworkBundle\Model\Advert\AdvertFacade
     */
    protected $advertFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Advert\AdvertDataFactory
     */
    private $advertDataFactory;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Advert\AdvertFacade $advertFacade
     * @param \Shopsys\ShopBundle\Model\Advert\AdvertDataFactory $advertDataFactory
     */
    public function __construct(AdvertFacade $advertFacade, AdvertDataFactory $advertDataFactory)
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

        $advertData->domainId = 1;
        $advertData->name = 'Prohlédnout';
        $advertData->type = Advert::TYPE_IMAGE;
        $advertData->positionName = 'sixthRectangle';
        $advertData->link = '/admin';
        $advertData->smallTitle = 'Legenární kapsáče jsou zpět';
        $advertData->bigTitle = 'Silnější než kdy předtím...';
        $advertData->productTitle = 'Jindra má na sobě';
        $advertData->products = [
            $this->getReference(ProductDataFixture::PRODUCT_PREFIX . '1'),
            $this->getReference(ProductDataFixture::PRODUCT_PREFIX . '76'),
            $this->getReference(ProductDataFixture::PRODUCT_PREFIX . '80'),
        ];
        $advert = $this->advertFacade->create($advertData);
        $this->addReference(self::ADVERT_SIXTH_RECTANGLE, $advert);
    }

    /**
     * @inheritDoc
     */
    public function getDependencies(): array
    {
        return [
            ProductDataFixture::class,
        ];
    }
}
