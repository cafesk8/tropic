<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Model\Store\StoreFacade;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class StoreController extends FrontBaseController
{
    private StoreFacade $storeFacade;

    /**
     * @param \App\Model\Store\StoreFacade $storeFacade
     */
    public function __construct(StoreFacade $storeFacade)
    {
        $this->storeFacade = $storeFacade;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction(Request $request): Response
    {
        $storeId = $request->query->getInt('pickupPlaceId');
        $stores = $this->storeFacade->getAllPickupPlaces();
        $chosenStore = null;

        if ($storeId > 0) {
            $chosenStore = $this->storeFacade->getById($storeId);

            if (!in_array($chosenStore, $stores, true)) {
                $chosenStore = null;
            }
        }

        return $this->render('Front/Inline/Store/list.html.twig', [
            'stores' => $stores,
            'chosenStore' => $chosenStore,
        ]);
    }
}