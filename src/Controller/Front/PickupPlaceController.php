<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Model\Transport\PickupPlace\PickupPlaceFacade;
use Shopsys\FrameworkBundle\Component\String\TransformString;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PickupPlaceController extends FrontBaseController
{
    /**
     * @var \App\Model\Transport\PickupPlace\PickupPlaceFacade
     */
    protected $pickupPlaceFacade;

    /**
     * @param \App\Model\Transport\PickupPlace\PickupPlaceFacade $pickUpPlaceFacade
     */
    public function __construct(PickupPlaceFacade $pickUpPlaceFacade)
    {
        $this->pickupPlaceFacade = $pickUpPlaceFacade;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searchAction(Request $request): Response
    {
        $pickupPlaceId = $request->query->getInt('pickupPlaceId');
        $transportId = $request->query->getInt('transportId');

        $pickupPlaces = $this->pickupPlaceFacade->getAllForTransportId($transportId);

        $chosenPickupPlace = null;
        if ($pickupPlaceId > 0) {
            $chosenPickupPlace = $this->pickupPlaceFacade->getById((int)$pickupPlaceId);

            if (in_array($chosenPickupPlace, $pickupPlaces, true) === false) {
                $chosenPickupPlace = null;
            }
        }

        return $this->render('Front/Inline/PickupPlace/pickupPlaceSearch.html.twig', [
            'pickupPlaces' => $pickupPlaces,
            'chosenPickupPlace' => $chosenPickupPlace,
        ]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function autocompleteAction(Request $request): Response
    {
        $rawSearchQuery = $request->request->get('searchQuery', '');
        $transportId = $request->request->getInt('transportId');

        $searchQuery = TransformString::emptyToNull(trim($rawSearchQuery));

        if ($searchQuery === null) {
            $pickupPlaces = $this->pickupPlaceFacade->getAllForTransportId($transportId);
        } else {
            $pickupPlaces = $this->pickupPlaceFacade->findActiveBySearchQueryAndTransportId(
                $searchQuery,
                $transportId
            );
        }

        return $this->render('Front/Inline/PickupPlace/autocompleteResult.html.twig', [
            'pickupPlaces' => $pickupPlaces,
            'chosenPickupPlace' => null,
        ]);
    }
}
