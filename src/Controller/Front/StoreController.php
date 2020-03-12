<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Component\Setting\Setting;
use App\Model\Article\ArticleFacade;
use App\Model\Store\StoreFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class StoreController extends FrontBaseController
{
    /**
     * @var \App\Model\Store\StoreFacade
     */
    private $storeFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \App\Model\Article\ArticleFacade
     */
    private $articleFacade;

    /**
     * @param \App\Model\Store\StoreFacade $storeFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Model\Article\ArticleFacade $articleFacade
     */
    public function __construct(StoreFacade $storeFacade, Domain $domain, ArticleFacade $articleFacade)
    {
        $this->storeFacade = $storeFacade;
        $this->domain = $domain;
        $this->articleFacade = $articleFacade;
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

            if (in_array($chosenStore, $stores, true) === false) {
                $chosenStore = null;
            }
        }

        return $this->render('Front/Inline/Store/list.html.twig', [
            'stores' => $stores,
            'chosenStore' => $chosenStore,
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(): Response
    {
        return $this->render('Front/Content/Stores/index.html.twig', [
            'regions' => $this->storeFacade->findRegionNamesForStoreList(),
            'storesIndexedByRegion' => $this->storeFacade->findStoresForStoreListIndexedByRegion(),
        ]);
    }

    /**
     * @param int $storeId
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function detailAction(int $storeId): Response
    {
        $store = $this->storeFacade->getStoreForStoreListById($storeId);

        $loyaltyProgramArticle = $this->articleFacade->findArticleBySettingValueAndDomainId(
            Setting::LOYALTY_PROGRAM_ARTICLE_ID,
            $this->domain->getId()
        );

        return $this->render('Front/Content/Stores/detail.html.twig', [
            'store' => $store,
            'loyaltyProgramArticle' => $loyaltyProgramArticle,
        ]);
    }
}
