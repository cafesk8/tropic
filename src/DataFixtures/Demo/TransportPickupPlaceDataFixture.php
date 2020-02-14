<?php

declare(strict_types=1);

namespace App\DataFixtures\Demo;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Generator;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;
use App\Component\String\StringHelper;
use App\Model\Transport\PickupPlace\PickupPlaceData;
use App\Model\Transport\PickupPlace\PickupPlaceFacade;

class TransportPickupPlaceDataFixture extends AbstractReferenceFixture implements DependentFixtureInterface
{
    public const BALIKOBOT_SHIPPER = 'cp';
    public const BALIKOBOT_SHIPPER_SERVICE = 'NP';

    /**
     * @var \App\Model\Transport\PickupPlace\PickupPlaceFacade
     */
    private $pickupPlaceFacade;

    /**
     * @var \Faker\Generator
     */
    private $faker;

    /**
     * @param \App\Model\Transport\PickupPlace\PickupPlaceFacade $pickupPlaceFacade
     * @param \Faker\Generator $faker
     */
    public function __construct(PickupPlaceFacade $pickupPlaceFacade, Generator $faker)
    {
        $this->pickupPlaceFacade = $pickupPlaceFacade;
        $this->faker = $faker;
    }

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        $pickupPlacesData = [];

        for ($index = 0; $index < 10; $index++) {
            $pickupPlaceData = new PickupPlaceData();
            $pickupPlaceData->balikobotId = 'test_' . $index;
            $pickupPlaceData->balikobotShipper = self::BALIKOBOT_SHIPPER;
            $pickupPlaceData->balikobotShipperService = self::BALIKOBOT_SHIPPER_SERVICE;

            $pickupPlaceData->name = 'Testovací pobočka ' . $index;
            $pickupPlaceData->city = $this->faker->city;
            $pickupPlaceData->street = $this->faker->streetAddress;
            $pickupPlaceData->postCode = StringHelper::removeWhitespaces($this->faker->postcode);
            $pickupPlaceData->countryCode = 'CZ';

            $pickupPlacesData[] = $pickupPlaceData;
        }

        $this->pickupPlaceFacade->createFromArray($pickupPlacesData);
    }

    /**
     * This method must return an array of fixtures classes
     * on which the implementing class depends on
     *
     * @return array
     */
    public function getDependencies(): array
    {
        return [
            TransportDataFixture::class,
        ];
    }
}
