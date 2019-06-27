<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\BushmanClub;

use DateTime;

class BushmanClubPointPeriod
{
    /**
     * @var \DateTime
     */
    private $dateFrom;

    /**
     * @var \DateTime
     */
    private $dateTo;

    /**
     * @param string $dateFrom
     * @param string $dateTo
     * @param string $year
     */
    public function __construct(string $dateFrom, string $dateTo, string $year)
    {
        $this->setDateTimePeriodFromDate($dateFrom, $dateTo, $year);
    }

    /**
     * @param string $dateFrom
     * @param string $dateTo
     * @param string $year
     */
    private function setDateTimePeriodFromDate(string $dateFrom, string $dateTo, string $year): void
    {
        $dateFrom = DateTime::createFromFormat('m-d-Y H:i:s', sprintf('%s-%s 00:00:00', $dateFrom, $year));
        $dateTo = DateTime::createFromFormat('m-d-Y H:i:s', sprintf('%s-%s 00:00:00', $dateTo, $year));

        if ($dateFrom >= $dateTo) {
            $dateFrom->modify('-1 year');
        }

        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }

    /**
     * @return \DateTime
     */
    public function getDateFrom(): DateTime
    {
        return $this->dateFrom;
    }

    /**
     * @return \DateTime
     */
    public function getDateTo(): DateTime
    {
        return $this->dateTo;
    }
}
