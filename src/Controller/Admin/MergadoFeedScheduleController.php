<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Component\Cron\CronModuleFacade;
use App\Model\Feed\DailyFeedCronModule;
use Shopsys\FrameworkBundle\Controller\Admin\AdminBaseController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MergadoFeedScheduleController extends AdminBaseController
{    
    private CronModuleFacade $cronModuleFacade;

    /**
     * @param \App\Component\Cron\CronModuleFacade $cronModuleFacade
     */
    public function __construct(
        CronModuleFacade $cronModuleFacade
    ) {
        $this->cronModuleFacade = $cronModuleFacade;
    }

    /**
     * @Route("/schedule/mergado-feed")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function scheduleMergadoFeedExport(): Response
    {
        $this->cronModuleFacade->schedule(DailyFeedCronModule::class);

        $this->addSuccessFlash(
            t('Feed successfully generated.'),
        );

        return $this->redirectToRoute('admin_feed_list');
    }
}