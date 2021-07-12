<?php

declare(strict_types=1);

namespace App\Model\Category;

use App\Model\Product\Parameter\ParameterFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Image\ImageFacade;
use Shopsys\FrameworkBundle\Component\Plugin\PluginCrudExtensionFacade;
use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlFacade;
use Shopsys\FrameworkBundle\Model\Category\Category as BaseCategory;
use Shopsys\FrameworkBundle\Model\Category\CategoryData as BaseCategoryData;
use Shopsys\FrameworkBundle\Model\Category\CategoryDataFactory as BaseCategoryDataFactory;
use Shopsys\FrameworkBundle\Model\Category\CategoryRepository;

class CategoryDataFactory extends BaseCategoryDataFactory
{
    private ParameterFacade $parameterFacade;

    /**
     * @param \App\Model\Category\CategoryRepository $categoryRepository
     * @param \App\Component\Router\FriendlyUrl\FriendlyUrlFacade $friendlyUrlFacade
     * @param \Shopsys\FrameworkBundle\Component\Plugin\PluginCrudExtensionFacade $pluginCrudExtensionFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Component\Image\ImageFacade $imageFacade
     * @param \App\Model\Product\Parameter\ParameterFacade $parameterFacade
     */
    public function __construct(
        CategoryRepository $categoryRepository,
        FriendlyUrlFacade $friendlyUrlFacade,
        PluginCrudExtensionFacade $pluginCrudExtensionFacade,
        Domain $domain,
        ImageFacade $imageFacade,
        ParameterFacade $parameterFacade
    ) {
        parent::__construct($categoryRepository, $friendlyUrlFacade, $pluginCrudExtensionFacade, $domain, $imageFacade);
        $this->parameterFacade = $parameterFacade;
    }

    /**
     * @param \App\Model\Category\Category $category
     * @return \App\Model\Category\CategoryData
     */
    public function createFromCategory(BaseCategory $category): BaseCategoryData
    {
        $categoryData = new CategoryData();
        $this->fillFromCategory($categoryData, $category);

        return $categoryData;
    }

    /**
     * @return \App\Model\Category\CategoryData
     */
    public function create(): BaseCategoryData
    {
        $categoryData = new CategoryData();
        $this->fillNew($categoryData);

        return $categoryData;
    }

    /**
     * @param \App\Model\Category\CategoryData $categoryData
     */
    protected function fillNew(BaseCategoryData $categoryData)
    {
        parent::fillNew($categoryData);
        $categoryData->filterParameters = $this->parameterFacade->getAll();

        foreach ($this->domain->getAllIds() as $domainId) {
            $categoryData->containsSaleProducts[$domainId] = false;
            $categoryData->containsNewsProducts[$domainId] = false;
            $categoryData->tipShown[$domainId] = false;
            $categoryData->tipName[$domainId] = null;
            $categoryData->tipText[$domainId] = null;
            $categoryData->tipProduct[$domainId] = null;
        }

        $categoryData->unavailableProductsShown = true;
    }

    /**
     * @param \App\Model\Category\CategoryData $categoryData
     * @param \App\Model\Category\Category $category
     */
    protected function fillFromCategory(BaseCategoryData $categoryData, BaseCategory $category)
    {
        parent::fillFromCategory($categoryData, $category);

        $categoryData->listable = $category->isListable();
        $categoryData->preListingCategory = $category->isPreListingCategory();
        $categoryData->mallCategoryId = $category->getMallCategoryId();
        $categoryData->leftBannerTexts = $category->getLeftBannerTexts();
        $categoryData->rightBannerTexts = $category->getRightBannerTexts();
        $categoryData->adverts = $category->getAdverts();
        $categoryData->pohodaId = $category->getPohodaId();
        $categoryData->pohodaParentId = $category->getPohodaParentId();
        $categoryData->updatedByPohodaAt = $category->getUpdatedByPohodaAt();
        $categoryData->pohodaPosition = $category->getPohodaPosition();
        $categoryData->type = $category->getType();
        $categoryData->filterParameters = $category->getFilterParameters();
        $categoryData->containsSaleProducts = $category->containsSaleProducts();
        $categoryData->containsNewsProducts = $category->containsNewsProducts();
        $categoryData->unavailableProductsShown = $category->isUnavailableProductsShown();
        $categoryData->tipShown = $category->areTipsShown();
        $categoryData->tipName = $category->getTipNames();
        $categoryData->tipText = $category->getTipTexts();
        $categoryData->tipProduct = $category->getTipProducts();
    }
}
