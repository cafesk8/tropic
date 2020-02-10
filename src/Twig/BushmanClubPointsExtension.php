<?php

declare(strict_types=1);

namespace App\Twig;

use App\Model\BushmanClub\BushmanClubPointPeriod;
use App\Model\BushmanClub\BushmanClubPointsFacade;
use App\Model\BushmanClub\CurrentBushmanClubPointPeriods;
use Shopsys\FrameworkBundle\Twig\NumberFormatterExtension;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class BushmanClubPointsExtension extends AbstractExtension
{
    /**
     * @var \App\Twig\NumberFormatterExtension
     */
    private $numberFormatterExtension;

    /**
     * @var \App\Model\BushmanClub\BushmanClubPointsFacade
     */
    private $bushmanClubPointsFacade;

    /**
     * @var \App\Model\BushmanClub\CurrentBushmanClubPointPeriods
     */
    private $bushmanClubPointPeriodSettings;

    /**
     * @param \App\Twig\NumberFormatterExtension $numberFormatterExtension
     * @param \App\Model\BushmanClub\BushmanClubPointsFacade $bushmanClubPointsFacade
     * @param \App\Model\BushmanClub\CurrentBushmanClubPointPeriods $bushmanClubPointPeriodSettings
     */
    public function __construct(NumberFormatterExtension $numberFormatterExtension, BushmanClubPointsFacade $bushmanClubPointsFacade, CurrentBushmanClubPointPeriods $bushmanClubPointPeriodSettings)
    {
        $this->numberFormatterExtension = $numberFormatterExtension;
        $this->bushmanClubPointsFacade = $bushmanClubPointsFacade;
        $this->bushmanClubPointPeriodSettings = $bushmanClubPointPeriodSettings;
    }

    /**
     * @return array
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('bushmanClubPoints', [$this, 'bushmanClubPoints']),
            new TwigFunction('bushmanClubPointsByIndex', [$this, 'bushmanClubPointsByIndex']),
        ];
    }

    /**
     * @param int $customerId
     * @param \App\Model\BushmanClub\BushmanClubPointPeriod $bushmanClubPointPeriod
     * @return string|null
     */
    public function bushmanClubPoints(int $customerId, BushmanClubPointPeriod $bushmanClubPointPeriod): ?string
    {
        return $this->numberFormatterExtension->formatNumber($this->bushmanClubPointsFacade->calculatePointsForCustomerAndPeriod($customerId, $bushmanClubPointPeriod));
    }

    /**
     * @param int $customerId
     * @param string $periodName
     * @return string|null
     */
    public function bushmanClubPointsByIndex(int $customerId, string $periodName): ?string
    {
        $bushmanClubPointPeriod = $this->bushmanClubPointPeriodSettings->getPeriod($periodName);
        return $this->numberFormatterExtension->formatNumber($this->bushmanClubPointsFacade->calculatePointsForCustomerAndPeriod($customerId, $bushmanClubPointPeriod));
    }
}
