<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Front;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\ShopBundle\Component\Setting\Setting;
use Shopsys\ShopBundle\Model\Article\ArticleFacade;
use Shopsys\ShopBundle\Model\Store\StoreFacade;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class StoreController extends FrontBaseController
{
    /**
     * @var \Shopsys\ShopBundle\Model\Store\StoreFacade
     */
    private $storeFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \Shopsys\ShopBundle\Model\Article\ArticleFacade
     */
    private $articleFacade;

    /**
     * @param \Shopsys\ShopBundle\Model\Store\StoreFacade $storeFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\ShopBundle\Model\Article\ArticleFacade $articleFacade
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

        $stores = $this->storeFacade->getAllPickupPlacesForDomain($this->domain->getCurrentDomainConfig()->getId());

        $chosenStore = null;
        if ($storeId > 0) {
            $chosenStore = $this->storeFacade->getById($storeId);

            if (in_array($chosenStore, $stores, true) === false) {
                $chosenStore = null;
            }
        }

        return $this->render('@ShopsysShop/Front/Inline/Store/list.html.twig', [
            'stores' => $stores,
            'chosenStore' => $chosenStore,
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(): Response
    {
        return $this->render('@ShopsysShop/Front/Content/Stores/index.html.twig', [
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
        $store = $this->storeFacade->getStoreForDomainAndForStoreListById($storeId);

        $bushmanClubArticle = $this->articleFacade->findArticleBySettingValueAndDomainId(
            Setting::BUSHMAN_CLUB_ARTICLE_ID,
            $this->domain->getId()
        );

        return $this->render('@ShopsysShop/Front/Content/Stores/detail.html.twig', [
            'store' => $store,
            'bushmanClubArticle' => $bushmanClubArticle,
        ]);
    }
}
