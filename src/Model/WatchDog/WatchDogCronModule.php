<?php

declare(strict_types=1);

namespace App\Model\WatchDog;

use Exception;
use Shopsys\Plugin\Cron\SimpleCronModuleInterface;
use Symfony\Bridge\Monolog\Logger;

class WatchDogCronModule implements SimpleCronModuleInterface
{
    /**
     * @var \Symfony\Bridge\Monolog\Logger
     */
    private $logger;

    /**
     * @var \App\Model\WatchDog\WatchDogFacade
     */
    private $watchDogFacade;

    /**
     * @param \App\Model\WatchDog\WatchDogFacade $watchDogFacade
     */
    public function __construct(WatchDogFacade $watchDogFacade)
    {
        $this->watchDogFacade = $watchDogFacade;
    }

    /**
     * @param \Symfony\Bridge\Monolog\Logger $logger
     */
    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function run()
    {
        $this->logger->addInfo('Začátek odesílání informačních emailů z Hlídače cen a dostupnosti');
        $watchDogs = $this->watchDogFacade->getAllVisible();
        $sentCount = 0;

        foreach ($watchDogs as $watchDog) {
            if ($this->watchDogFacade->shouldBeSent($watchDog)) {
                try {
                    $this->watchDogFacade->sendMail($watchDog);
                    $this->watchDogFacade->delete($watchDog);
                    $sentCount++;
                } catch (Exception $exception) {
                    $this->logger->addError('Odeslání informačního emailu z Hlídače ceny a dostupnost se nezdařilo', [
                        'message' => $exception->getMessage(),
                        'watchDog' => $watchDog,
                    ]);
                }
            }
        }

        $this->logger->addInfo('Bylo odesláno ' . $sentCount . ' informačních emailů z Hlídače cen a dostupnosti');

        return false;
    }
}
