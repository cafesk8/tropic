<?php

declare(strict_types=1);

namespace App\DataFixtures\Demo;

use App\Model\Advert\AdvertDataFactory;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;
use Shopsys\FrameworkBundle\Model\Advert\Advert;
use Shopsys\FrameworkBundle\Model\Advert\AdvertFacade;

class AdvertDataFixture extends AbstractReferenceFixture implements DependentFixtureInterface
{
    public const ADVERT_FIRST_SQUARE = 'advert_first_square';
    public const ADVERT_SECOND_SQUARE = 'advert_second_square';
    public const ADVERT_THIRD_SQUARE = 'advert_third_square';

    /**
     * @var \App\Model\Advert\AdvertFacade
     */
    protected $advertFacade;

    /**
     * @var \App\Model\Advert\AdvertDataFactory
     */
    private $advertDataFactory;

    /**
     * @param \App\Model\Advert\AdvertFacade $advertFacade
     * @param \App\Model\Advert\AdvertDataFactory $advertDataFactory
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
        $advertData->name = t('Shopsys', [], 'dataFixtures');
        $advertData->type = Advert::TYPE_IMAGE;
        $advertData->hidden = false;
        $advertData->positionName = 'firstSquare';
        $advertData->link = 'https://www.shopsys.com';
        $advert = $this->advertFacade->create($advertData);
        $this->addReference(self::ADVERT_FIRST_SQUARE, $advert);

        $advertData->domainId = 1;
        $advertData->name = t('Open-source Ecommerce Framework', [], 'dataFixtures');
        $advertData->type = Advert::TYPE_IMAGE;
        $advertData->hidden = false;
        $advertData->positionName = 'secondSquare';
        $advertData->link = 'https://www.shopsys.com/showcase';
        $advert = $this->advertFacade->create($advertData);
        $this->addReference(self::ADVERT_SECOND_SQUARE, $advert);

        $advertData->domainId = 1;
        $advertData->name = t('Eshopov?? hv??zda', [], 'dataFixtures');
        $advertData->type = Advert::TYPE_IMAGE;
        $advertData->hidden = false;
        $advertData->positionName = 'thirdSquare';
        $advertData->link = 'https://www.shopsys.cz/shopsys-b2c-commerce-cloud';
        $advert = $this->advertFacade->create($advertData);
        $this->addReference(self::ADVERT_THIRD_SQUARE, $advert);
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
