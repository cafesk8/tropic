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
        $advertData->name = 'Shopsys';
        $advertData->type = Advert::TYPE_IMAGE;
        $advertData->hidden = false;
        $advertData->positionName = 'firstSquare';
        $advertData->link = 'https://www.shopsys.com';
        $advert = $this->advertFacade->create($advertData);
        $this->addReference(self::ADVERT_FIRST_SQUARE, $advert);

        $advertData->domainId = 1;
        $advertData->name = 'Open-source Ecommerce Framework';
        $advertData->type = Advert::TYPE_IMAGE;
        $advertData->hidden = false;
        $advertData->positionName = 'secondSquare';
        $advertData->link = 'https://www.shopsys.com/showcase';
        $advert = $this->advertFacade->create($advertData);
        $this->addReference(self::ADVERT_SECOND_SQUARE, $advert);

        $advertData->domainId = 1;
        $advertData->name = 'Eshopová hvězda';
        $advertData->type = Advert::TYPE_IMAGE;
        $advertData->hidden = false;
        $advertData->positionName = 'thirdSquare';
        $advertData->link = 'https://www.shopsys.cz/shopsys-b2c-commerce-cloud';
        $advert = $this->advertFacade->create($advertData);
        $this->addReference(self::ADVERT_THIRD_SQUARE, $advert);

        $advertData->domainId = 1;
        $advertData->name = 'Shopsys';
        $advertData->type = Advert::TYPE_IMAGE;
        $advertData->hidden = false;
        $advertData->positionName = 'fourthSquare';
        $advertData->link = 'https://www.shopsys.cz/kontakt/';
        $advert = $this->advertFacade->create($advertData);
        $this->addReference(self::ADVERT_FOURTH_SQUARE, $advert);

        $advertData->domainId = 1;
        $advertData->name = 'Zelí';
        $advertData->type = Advert::TYPE_IMAGE;
        $advertData->hidden = false;
        $advertData->positionName = 'fifthRectangle';
        $advertData->link = '/';
        $advert = $this->advertFacade->create($advertData);
        $this->addReference(self::ADVERT_FIFTH_RECTANGLE, $advert);

        $advertData->domainId = 1;
        $advertData->name = 'Pocit';
        $advertData->type = Advert::TYPE_IMAGE;
        $advertData->positionName = 'sixthRectangle';
        $advertData->link = '/';
        $advertData->smallTitle = 'Když svítí slunce';
        $advertData->bigTitle = 'Překvapit by však mohl fakt, že nikdo, snad krom toho, kdo balónky k obloze vypustil, netuší, jakou má ona stuha barvu.';
        $advertData->productTitle = 'Ptají se často lidé.';
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
