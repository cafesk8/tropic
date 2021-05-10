<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Category\Transfer\CategoryImportCronModule;
use Shopsys\FrameworkBundle\Controller\Admin\DefaultController as BaseDefaultController;
use Shopsys\FrameworkBundle\Form\Admin\QuickSearch\QuickSearchFormData;
use Shopsys\FrameworkBundle\Form\Admin\QuickSearch\QuickSearchFormType;
use Shopsys\FrameworkBundle\Model\Product\Elasticsearch\ProductExportCronModule;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @property \App\Component\Setting\Setting $setting
 * @property \App\Model\Product\Availability\AvailabilityFacade $availabilityFacade
 * @property \App\Component\Cron\CronModuleFacade $cronModuleFacade
 * @method __construct(\App\Model\Statistics\StatisticsFacade $statisticsFacade, \App\Model\Statistics\StatisticsProcessingFacade $statisticsProcessingFacade, \Shopsys\FrameworkBundle\Model\Mail\MailTemplateFacade $mailTemplateFacade, \Shopsys\FrameworkBundle\Model\Product\Unit\UnitFacade $unitFacade, \App\Component\Setting\Setting $setting, \App\Model\Product\Availability\AvailabilityFacade $availabilityFacade, \App\Component\Cron\CronModuleFacade $cronModuleFacade, \Shopsys\FrameworkBundle\Component\Grid\GridFactory $gridFactory, \App\Component\Cron\Config\CronConfig $cronConfig, \App\Component\Cron\CronFacade $cronFacade)
 * @property \App\Component\Cron\Config\CronConfig $cronConfig
 * @property \App\Component\Cron\CronFacade $cronFacade
 * @property \App\Model\Statistics\StatisticsFacade $statisticsFacade
 * @property \App\Model\Statistics\StatisticsProcessingFacade $statisticsProcessingFacade
 */
class DefaultController extends BaseDefaultController
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function dashboardAction(): Response
    {
        $registeredInLastTwoWeeks = $this->statisticsFacade->getCustomersRegistrationsCountByDayInLastTwoWeeks();
        $registeredInLastTwoWeeksDates = $this->statisticsProcessingFacade->getDateTimesFormattedToLocaleFormat($registeredInLastTwoWeeks);
        $registeredInLastTwoWeeksCounts = $this->statisticsProcessingFacade->getCounts($registeredInLastTwoWeeks);
        $newOrdersCountByDayInLastTwoWeeks = $this->statisticsFacade->getNewOrdersCountByDayInLastTwoWeeks();
        $newOrdersInLastTwoWeeksDates = $this->statisticsProcessingFacade->getDateTimesFormattedToLocaleFormat($newOrdersCountByDayInLastTwoWeeks);
        $newOrdersInLastTwoWeeksCounts = $this->statisticsProcessingFacade->getCounts($newOrdersCountByDayInLastTwoWeeks);

        $quickProductSearchData = new QuickSearchFormData();
        $quickProductSearchForm = $this->createForm(QuickSearchFormType::class, $quickProductSearchData, [
            'action' => $this->generateUrl('admin_product_list'),
        ]);

        $currentCountOfOrders = $this->statisticsFacade->getOrdersCount(static::PREVIOUS_DAYS_TO_LOAD_STATISTICS_FOR);
        $previousCountOfOrders = $this->statisticsFacade->getOrdersCount(
            static::PREVIOUS_DAYS_TO_LOAD_STATISTICS_FOR * 2,
            static::PREVIOUS_DAYS_TO_LOAD_STATISTICS_FOR
        );

        $ordersTrend = $this->getTrendDifference($previousCountOfOrders, $currentCountOfOrders);

        $currentCountOfNewCustomers = $this->statisticsFacade->getNewCustomersCount(static::PREVIOUS_DAYS_TO_LOAD_STATISTICS_FOR);
        $previousCountOfNewCustomers = $this->statisticsFacade->getNewCustomersCount(
            static::PREVIOUS_DAYS_TO_LOAD_STATISTICS_FOR * 2,
            static::PREVIOUS_DAYS_TO_LOAD_STATISTICS_FOR
        );

        $newCustomersTrend = $this->getTrendDifference($previousCountOfNewCustomers, $currentCountOfNewCustomers);

        $currentValueOfOrders = $this->statisticsFacade->getOrdersValue(static::PREVIOUS_DAYS_TO_LOAD_STATISTICS_FOR);
        $previousValueOfOrders = $this->statisticsFacade->getOrdersValue(
            static::PREVIOUS_DAYS_TO_LOAD_STATISTICS_FOR * 2,
            static::PREVIOUS_DAYS_TO_LOAD_STATISTICS_FOR
        );

        $ordersValueTrend = $this->getTrendDifference($previousValueOfOrders, $currentValueOfOrders);

        $this->addWarningMessagesOnDashboard();

        return $this->render(
            '@ShopsysFramework/Admin/Content/Default/index.html.twig',
            [
                'registeredInLastTwoWeeksLabels' => $registeredInLastTwoWeeksDates,
                'registeredInLastTwoWeeksValues' => $registeredInLastTwoWeeksCounts,
                'newOrdersInLastTwoWeeksLabels' => $newOrdersInLastTwoWeeksDates,
                'newOrdersInLastTwoWeeksValues' => $newOrdersInLastTwoWeeksCounts,
                'newOrdersInLastTwoWeeksSums' => $this->statisticsProcessingFacade->getSums($newOrdersCountByDayInLastTwoWeeks),
                'quickProductSearchForm' => $quickProductSearchForm->createView(),
                'newCustomers' => $currentCountOfNewCustomers,
                'newCustomersTrend' => $newCustomersTrend,
                'newOrders' => $currentCountOfOrders,
                'newOrdersTrend' => $ordersTrend,
                'ordersValue' => $currentValueOfOrders,
                'ordersValueTrend' => $ordersValueTrend,
                'cronGridViews' => $this->getCronGridViews(),
            ]
        );
    }

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
