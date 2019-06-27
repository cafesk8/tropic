<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Twig;

use Shopsys\FrameworkBundle\Twig\NumberFormatterExtension;
use Shopsys\ShopBundle\Model\BushmanClub\BushmanClubPointPeriod;
use Shopsys\ShopBundle\Model\BushmanClub\BushmanClubPointsFacade;
use Shopsys\ShopBundle\Model\BushmanClub\CurrentBushmanClubPointPeriods;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class BushmanClubPointsExtension extends AbstractExtension
{
    /**
     * @var \Shopsys\FrameworkBundle\Twig\NumberFormatterExtension
     */
    private $numberFormatterExtension;

    /**
     * @var \Shopsys\ShopBundle\Model\BushmanClub\BushmanClubPointsFacade
     */
    private $bushmanClubPointsFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\BushmanClub\CurrentBushmanClubPointPeriods
     */
    private $bushmanClubPointPeriodSettings;

    /**
     * @param \Shopsys\FrameworkBundle\Twig\NumberFormatterExtension $numberFormatterExtension
     * @param \Shopsys\ShopBundle\Model\BushmanClub\BushmanClubPointsFacade $bushmanClubPointsFacade
     * @param \Shopsys\ShopBundle\Model\BushmanClub\CurrentBushmanClubPointPeriods $bushmanClubPointPeriodSettings
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
     * @param \Shopsys\ShopBundle\Model\BushmanClub\BushmanClubPointPeriod $bushmanClubPointPeriod
     * @return string|null
     */
    public function bushmanClubPoints(int $customerId, BushmanClubPointPeriod $bushmanClubPointPeriod): ?string
    {
        return $this->numberFormatterExtension->formatNumber($this->bushmanClubPointsFacade->calculatePointsForCustomerAndPeriod($customerId, $bushmanClubPointPeriod));
    }

    /**
     * @param int $customerId
     * @param int $periodName
     * @return string|null
     */
    public function bushmanClubPointsByIndex(int $customerId, string $periodName): ?string
    {
        $bushmanClubPointPeriod = $this->bushmanClubPointPeriodSettings->getPeriod($periodName);
        return $this->numberFormatterExtension->formatNumber($this->bushmanClubPointsFacade->calculatePointsForCustomerAndPeriod($customerId, $bushmanClubPointPeriod));
    }
}
