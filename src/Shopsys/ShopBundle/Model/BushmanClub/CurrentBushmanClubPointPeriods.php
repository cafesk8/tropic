<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\BushmanClub;

use DateTime;
use Shopsys\ShopBundle\Model\BushmanClub\Exceptions\BushmanClubPointPeriodNotFoundException;

class CurrentBushmanClubPointPeriods
{
    // these constants are month-day period in format 'm-d'
    private const FIRST_PERIOD_FROM = '08-16';
    private const FIRST_PERIOD_TO = '02-15';
    private const SECOND_PERIOD_FROM = '02-16';
    private const SECOND_PERIOD_TO = '08-15';

    /**
     * @var \Shopsys\ShopBundle\Model\BushmanClub\BushmanClubPointPeriod[]
     */
    private $periods = [];

    /**
     * @param \Shopsys\ShopBundle\Model\BushmanClub\BushmanClubPointPeriodsService $bushmanClubPointPeriodsService
     */
    public function __construct(BushmanClubPointPeriodsService $bushmanClubPointPeriodsService)
    {
        $this->periods = $bushmanClubPointPeriodsService->calculatePreviousAndActualPeriodForDateTime(
            self::FIRST_PERIOD_FROM,
            self::FIRST_PERIOD_TO,
            self::SECOND_PERIOD_FROM,
            self::SECOND_PERIOD_TO,
            new DateTime()
        );
    }

    /**
     * @return \Shopsys\ShopBundle\Model\BushmanClub\BushmanClubPointPeriod[]
     */
    public function getPeriods(): array
    {
        return $this->periods;
    }

    /**
     * @param string $periodName
     * @return \Shopsys\ShopBundle\Model\BushmanClub\BushmanClubPointPeriod
     */
    public function getPeriod(string $periodName): BushmanClubPointPeriod
    {
        if (array_key_exists($periodName, $this->periods) === false) {
            throw new BushmanClubPointPeriodNotFoundException(sprintf('Bushman club point period with index %d not found.', $periodName));
        }

        return $this->periods[$periodName];
    }
}
