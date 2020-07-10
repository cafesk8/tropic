<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Category\Transfer\CategoryImportCronModule;
use Shopsys\FrameworkBundle\Controller\Admin\DefaultController as BaseDefaultController;
use Shopsys\FrameworkBundle\Model\Product\Elasticsearch\ProductExportCronModule;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @property \App\Component\Setting\Setting $setting
 * @property \App\Model\Product\Availability\AvailabilityFacade $availabilityFacade
 * @property \App\Component\Cron\CronModuleFacade $cronModuleFacade
 * @method __construct(\Shopsys\FrameworkBundle\Model\Statistics\StatisticsFacade $statisticsFacade, \Shopsys\FrameworkBundle\Model\Statistics\StatisticsProcessingFacade $statisticsProcessingFacade, \Shopsys\FrameworkBundle\Model\Mail\MailTemplateFacade $mailTemplateFacade, \Shopsys\FrameworkBundle\Model\Product\Unit\UnitFacade $unitFacade, \App\Component\Setting\Setting $setting, \App\Model\Product\Availability\AvailabilityFacade $availabilityFacade, \App\Component\Cron\CronModuleFacade $cronModuleFacade, \Shopsys\FrameworkBundle\Component\Grid\GridFactory $gridFactory, \App\Component\Cron\Config\CronConfig $cronConfig, \Shopsys\FrameworkBundle\Component\Cron\CronFacade $cronFacade)
 * @property \App\Component\Cron\Config\CronConfig $cronConfig
 */
class DefaultController extends BaseDefaultController
{
    /**
     * @Route("/schedule/import-categories")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function scheduleCategoriesImport(): Response
    {
        $this->cronModuleFacade->schedule(CategoryImportCronModule::class);
        $this->cronModuleFacade->schedule(ProductExportCronModule::class);

        $this->addSuccessFlash(
            t('Import kategorií spolu s exportem do Elastic Search byl naplánován')
        );

        return $this->redirectToRoute('admin_default_dashboard');
    }
}
