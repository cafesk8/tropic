<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\HeurekaCategory;

use Shopsys\ProductFeed\HeurekaBundle\Model\HeurekaCategory\HeurekaCategoryDataFactoryInterface;
use Shopsys\ProductFeed\HeurekaBundle\Model\HeurekaCategory\HeurekaCategoryDownloader as BaseHeurekaCategoryDownloader;
use Shopsys\ProductFeed\HeurekaBundle\Model\HeurekaCategory\HeurekaCategoryFacade;
use Shopsys\ProductFeed\HeurekaBundle\Model\HeurekaCategory\HeurekaCategoryNotFoundException;

class HeurekaCategoryDownloader extends BaseHeurekaCategoryDownloader
{
    /**
     * @var \Shopsys\ProductFeed\HeurekaBundle\Model\HeurekaCategory\HeurekaCategoryFacade
     */
    private $heurekaCategoryFacade;

    /**
     * @param string $heurekaCategoryFeedUrl
     * @param \Shopsys\ProductFeed\HeurekaBundle\Model\HeurekaCategory\HeurekaCategoryDataFactoryInterface $heurekaCategoryDataFactory
     * @param \Shopsys\ProductFeed\HeurekaBundle\Model\HeurekaCategory\HeurekaCategoryFacade $heurekaCategoryFacade
     */
    public function __construct(
        string $heurekaCategoryFeedUrl,
        HeurekaCategoryDataFactoryInterface $heurekaCategoryDataFactory,
        HeurekaCategoryFacade $heurekaCategoryFacade
    ) {
        parent::__construct($heurekaCategoryFeedUrl, $heurekaCategoryDataFactory);
        $this->heurekaCategoryFacade = $heurekaCategoryFacade;
    }

    /**
     * @param \SimpleXMLElement[] $xmlCategoryDataObjects
     * @return \Shopsys\ProductFeed\HeurekaBundle\Model\HeurekaCategory\HeurekaCategoryData[]
     */
    protected function convertToShopEntities(array $xmlCategoryDataObjects)
    {
        $heurekaCategoriesData = parent::convertToShopEntities($xmlCategoryDataObjects);

        foreach ($heurekaCategoriesData as $heurekaCategoryData) {
            $heurekaCategoryId = $heurekaCategoryData->id;
            try {
                $heurekaCategory = $this->heurekaCategoryFacade->getOneById($heurekaCategoryId);
                $heurekaCategoryData->categories = $heurekaCategory->getCategories()->toArray();
            } catch (HeurekaCategoryNotFoundException $exception) {
            }
        }

        return $heurekaCategoriesData;
    }
}
