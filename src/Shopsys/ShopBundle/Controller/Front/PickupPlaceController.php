<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Front;

use Shopsys\FrameworkBundle\Component\String\TransformString;
use Shopsys\ShopBundle\Model\Transport\PickupPlace\Exception\PickupPlaceNotFoundException;
use Shopsys\ShopBundle\Model\Transport\PickupPlace\PickupPlaceFacade;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PickupPlaceController extends FrontBaseController
{
    /**
     * @var \Shopsys\ShopBundle\Model\Transport\PickupPlace\PickupPlaceFacade
     */
    protected $pickupPlaceFacade;

    /**
     * @param \Shopsys\ShopBundle\Model\Transport\PickupPlace\PickupPlaceFacade $pickUpPlaceFacade
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
        }

        return $this->render('@ShopsysShop/Front/Inline/PickupPlace/pickupPlaceSearch.html.twig', [
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

        return $this->render('@ShopsysShop/Front/Inline/PickupPlace/autocompleteResult.html.twig', [
            'pickupPlaces' => $pickupPlaces,
        ]);
    }
}
