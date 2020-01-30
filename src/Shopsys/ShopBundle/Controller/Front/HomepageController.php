<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Front;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Seo\SeoSettingFacade;
use Shopsys\FrameworkBundle\Model\Slider\SliderItemFacade;
use Shopsys\ReadModelBundle\Product\Listed\ListedProductViewFacadeInterface;
use Shopsys\ShopBundle\Component\Setting\Setting;
use Shopsys\ShopBundle\Model\Article\ArticleFacade;
use Shopsys\ShopBundle\Model\Blog\Article\BlogArticleFacade;
use Shopsys\ShopBundle\Model\Category\CategoryFacade;

class HomepageController extends FrontBaseController
{
    private const HOMEPAGE_ARTICLES_LIMIT = 2;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Seo\SeoSettingFacade
     */
    private $seoSettingFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Slider\SliderItemFacade
     */
    private $sliderItemFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \Shopsys\ShopBundle\Model\Blog\Article\BlogArticleFacade
     */
    private $blogArticleFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Category\CategoryFacade
     */
    private $categoryFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Article\ArticleFacade
     */
    private $articleFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\View\ListedProductViewElasticFacade
     */
    private $listedProductViewFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Slider\SliderItemFacade $sliderItemFacade
     * @param \Shopsys\FrameworkBundle\Model\Seo\SeoSettingFacade $seoSettingFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\ShopBundle\Model\Blog\Article\BlogArticleFacade $blogArticleFacade
     * @param \Shopsys\ShopBundle\Model\Category\CategoryFacade $categoryFacade
     * @param \Shopsys\ShopBundle\Model\Article\ArticleFacade $articleFacade
     * @param \Shopsys\ShopBundle\Model\Product\View\ListedProductViewElasticFacade $listedProductViewFacade
     */
    public function __construct(
        SliderItemFacade $sliderItemFacade,
        SeoSettingFacade $seoSettingFacade,
        Domain $domain,
        BlogArticleFacade $blogArticleFacade,
        CategoryFacade $categoryFacade,
        ArticleFacade $articleFacade,
        ListedProductViewFacadeInterface $listedProductViewFacade
    ) {
        $this->sliderItemFacade = $sliderItemFacade;
        $this->seoSettingFacade = $seoSettingFacade;
        $this->domain = $domain;
        $this->blogArticleFacade = $blogArticleFacade;
        $this->categoryFacade = $categoryFacade;
        $this->articleFacade = $articleFacade;
        $this->listedProductViewFacade = $listedProductViewFacade;
    }

    public function indexAction()
    {
        $sliderItems = $this->sliderItemFacade->getAllVisibleOnCurrentDomain();
        $topProducts = $this->listedProductViewFacade->getAllTop();

        return $this->render('@ShopsysShop/Front/Content/Default/index.html.twig', [
            'sliderItems' => $sliderItems,
            'topProducts' => $topProducts,
            'title' => $this->seoSettingFacade->getTitleMainPage($this->domain->getId()),
            'metaDescription' => $this->seoSettingFacade->getDescriptionMainPage($this->domain->getId()),
            'homepageBlogArticles' => $this->blogArticleFacade->getHomepageBlogArticlesByDomainId(
                $this->domain->getId(),
                $this->domain->getLocale(),
                self::HOMEPAGE_ARTICLES_LIMIT
            ),
            'domainId' => $this->domain->getId(),
            'legendaryCategoryId' => $this->categoryFacade->getHighestLegendaryCategoryIdByDomainId($this->domain->getId()),
            'loyaltyProgramArticle' => $this->articleFacade->findArticleBySettingValueAndDomainId(Setting::LOYALTY_PROGRAM_ARTICLE_ID, $this->domain->getId()),
            'ourValuesArticle' => $this->articleFacade->findArticleBySettingValueAndDomainId(Setting::OUR_VALUES_ARTICLE_ID, $this->domain->getId()),
            'ourStoryArticle' => $this->articleFacade->findArticleBySettingValueAndDomainId(Setting::OUR_STORY_ARTICLE_ID, $this->domain->getId()),
        ]);
    }
}
