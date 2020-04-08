<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Component\Setting\Setting;
use App\Model\Article\ArticleFacade;
use App\Model\Blog\Article\BlogArticleFacade;
use App\Model\Category\CategoryFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Seo\SeoSettingFacade;
use Shopsys\FrameworkBundle\Model\Slider\SliderItemFacade;
use Shopsys\ReadModelBundle\Product\Listed\ListedProductViewFacadeInterface;

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
     * @var \App\Model\Blog\Article\BlogArticleFacade
     */
    private $blogArticleFacade;

    /**
     * @var \App\Model\Category\CategoryFacade
     */
    private $categoryFacade;

    /**
     * @var \App\Model\Article\ArticleFacade
     */
    private $articleFacade;

    /**
     * @var \App\Model\Product\View\ListedProductViewElasticFacade
     */
    private $listedProductViewFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Slider\SliderItemFacade $sliderItemFacade
     * @param \Shopsys\FrameworkBundle\Model\Seo\SeoSettingFacade $seoSettingFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Model\Blog\Article\BlogArticleFacade $blogArticleFacade
     * @param \App\Model\Category\CategoryFacade $categoryFacade
     * @param \App\Model\Article\ArticleFacade $articleFacade
     * @param \App\Model\Product\View\ListedProductViewElasticFacade $listedProductViewFacade
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
        $priceBombProducts = $this->listedProductViewFacade->getPriceBombProducts(2);

        return $this->render('Front/Content/Default/index.html.twig', [
            'sliderItems' => $sliderItems,
            'topProducts' => $topProducts,
            'priceBombProducts' => $priceBombProducts,
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
        ]);
    }
}
