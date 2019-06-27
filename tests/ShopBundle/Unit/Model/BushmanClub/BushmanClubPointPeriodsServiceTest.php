<?php

declare(strict_types=1);

namespace Tests\ShopBundle\Unit\Model\BushmanClub;

use DateTime;
use PHPUnit\Framework\TestCase;
use Shopsys\ShopBundle\Model\BushmanClub\BushmanClubPointPeriodsService;

class BushmanClubPointPeriodsServiceTest extends TestCase
{
    /**
     * @param string $firstPeriodFrom
     * @param string $firstPeriodTo
     * @param string $secondPeriodFrom
     * @param string $secondPeriodTo
     * @param \DateTime $date
     * @param \DateTime $previousPeriodDateFrom
     * @param \DateTime $previousPeriodDateTo
     * @param \DateTime $actualPeriodDateFrom
     * @param \DateTime $actualPeriodDateTo
     * @dataProvider bushmanExpectedClubPointPeriodsByPeriodAndDateTimeConvertsProvider
     */
    public function testBushmanClubPointPeriodsServiceCalculateValidPreviousAndActualTimeFromDate(
        string $firstPeriodFrom,
        string $firstPeriodTo,
        string $secondPeriodFrom,
        string $secondPeriodTo,
        DateTime $date,
        DateTime $previousPeriodDateFrom,
        DateTime $previousPeriodDateTo,
        DateTime $actualPeriodDateFrom,
        DateTime $actualPeriodDateTo
    ): void {
        $bushmanClubPointPeriodsService = new BushmanClubPointPeriodsService();

        $periods = $bushmanClubPointPeriodsService->calculatePreviousAndActualPeriodForDateTime(
            $firstPeriodFrom,
            $firstPeriodTo,
            $secondPeriodFrom,
            $secondPeriodTo,
            $date
        );

        $previousPeriod = $periods[BushmanClubPointPeriodsService::PERIOD_PREVIOUS];
        $actualPeriod = $periods[BushmanClubPointPeriodsService::PERIOD_ACTUAL];

        $this->assertEquals($previousPeriodDateFrom, $previousPeriod->getDateFrom());
        $this->assertEquals($previousPeriodDateTo, $previousPeriod->getDateTo());
        $this->assertEquals($actualPeriodDateFrom, $actualPeriod->getDateFrom());
        $this->assertEquals($actualPeriodDateTo, $actualPeriod->getDateTo());
    }

    /**
     * @return array
     */
    public function bushmanExpectedClubPointPeriodsByPeriodAndDateTimeConvertsProvider(): array
    {
        return [
            [
                'firstPeriodFrom' => '08-16',
                'firstPeriodTo' => '02-15',
                'secondPeriodFrom' => '02-16',
                'secondPeriodTo' => '08-15',
                'date' => new DateTime('2019-01-01'),
                'previousPeriodDateFrom' => new DateTime('2018-02-16'),
                'previousPeriodDateTo' => new DateTime('2018-08-15'),
                'actualPeriodDateFrom' => new DateTime('2018-08-16'),
                'actualPeriodDateTo' => new DateTime('2019-02-15'),
            ],
            [
                'firstPeriodFrom' => '08-16',
                'firstPeriodTo' => '02-15',
                'secondPeriodFrom' => '02-16',
                'secondPeriodTo' => '08-15',
                'date' => new DateTime('2019-07-01'),
                'previousPeriodDateFrom' => new DateTime('2018-08-16'),
                'previousPeriodDateTo' => new DateTime('2019-02-15'),
                'actualPeriodDateFrom' => new DateTime('2019-02-16'),
                'actualPeriodDateTo' => new DateTime('2019-08-15'),
            ],
            [
                'firstPeriodFrom' => '08-16',
                'firstPeriodTo' => '02-15',
                'secondPeriodFrom' => '02-16',
                'secondPeriodTo' => '08-15',
                'date' => new DateTime('2019-10-01'),
                'previousPeriodDateFrom' => new DateTime('2019-02-16'),
                'previousPeriodDateTo' => new DateTime('2019-08-15'),
                'actualPeriodDateFrom' => new DateTime('2019-08-16'),
                'actualPeriodDateTo' => new DateTime('2020-02-15'),
            ],
        ];
    }
}
