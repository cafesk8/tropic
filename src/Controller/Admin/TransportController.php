<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use Shopsys\FrameworkBundle\Controller\Admin\TransportController as BaseTransportController;
use Shopsys\FrameworkBundle\Model\AdminNavigation\BreadcrumbOverrider;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFacade;
use Shopsys\FrameworkBundle\Model\Transport\Grid\TransportGridFactory;
use Shopsys\FrameworkBundle\Model\Transport\TransportDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Transport\TransportFacade;
use App\Component\Balikobot\Shipper\ShipperServiceFacade;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @property \App\Model\Pricing\Currency\CurrencyFacade $currencyFacade
 * @property \App\Model\Transport\TransportDataFactory $transportDataFactory
 * @property \App\Model\Transport\TransportFacade $transportFacade
 */
class TransportController extends BaseTransportController
{
    /**
     * @var \App\Component\Balikobot\Shipper\ShipperServiceFacade
     */
    private $shipperServiceFacade;

    /**
     * @param \App\Model\Transport\TransportFacade $transportFacade
     * @param \Shopsys\FrameworkBundle\Model\Transport\Grid\TransportGridFactory $transportGridFactory
     * @param \App\Model\Transport\TransportDataFactory $transportDataFactory
     * @param \App\Model\Pricing\Currency\CurrencyFacade $currencyFacade
     * @param \Shopsys\FrameworkBundle\Model\AdminNavigation\BreadcrumbOverrider $breadcrumbOverrider
     * @param \App\Component\Balikobot\Shipper\ShipperServiceFacade $shipperServiceFacade
     */
    public function __construct(
        TransportFacade $transportFacade,
        TransportGridFactory $transportGridFactory,
        TransportDataFactoryInterface $transportDataFactory,
        CurrencyFacade $currencyFacade,
        BreadcrumbOverrider $breadcrumbOverrider,
        ShipperServiceFacade $shipperServiceFacade
    ) {
        parent::__construct($transportFacade, $transportGridFactory, $transportDataFactory, $currencyFacade, $breadcrumbOverrider);
        $this->shipperServiceFacade = $shipperServiceFacade;
    }

    /**
     * @Route("/transport/balikobot-shipper-services/")
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listBalikobotShipperServicesAction(Request $request): Response
    {
        $shipper = $request->query->get('shipper', null);
        $responseArray = [];

        if ($shipper !== null) {
            $shipperServices = $this->shipperServiceFacade->getServicesForShipper($shipper);

            foreach ($shipperServices as $id => $name) {
                $responseArray[] = [
                    'id' => $id,
                    'name' => $name,
                ];
            }
        }

        return new JsonResponse($responseArray);
    }
}
