<?php

declare(strict_types=1);

namespace App\Model\Article;

use App\Component\Setting\Setting;

class ArticleSettingDataFactory
{
    /**
     * @var \App\Model\Article\ArticleFacade
     */
    private $articleFacade;

    /**
     * @param \App\Model\Article\ArticleFacade $articleFacade
     */
    public function __construct(ArticleFacade $articleFacade)
    {
        $this->articleFacade = $articleFacade;
    }

    /**
     * @param int $domainId
     * @return \App\Model\Article\ArticleSettingData
     */
    public function createFromSettingDataByDomainId(int $domainId): ArticleSettingData
    {
        $articleSettingData = new ArticleSettingData();

        $articleSettingData->loyaltyProgramArticle = $this->articleFacade->findArticleBySettingValueAndDomainId(Setting::LOYALTY_PROGRAM_ARTICLE_ID, $domainId);
        $articleSettingData->ourValuesArticle = $this->articleFacade->findArticleBySettingValueAndDomainId(Setting::OUR_VALUES_ARTICLE_ID, $domainId);
        $articleSettingData->ourStoryArticle = $this->articleFacade->findArticleBySettingValueAndDomainId(Setting::OUR_STORY_ARTICLE_ID, $domainId);
        $articleSettingData->firstArticleOnHeaderMenu = $this->articleFacade->findArticleBySettingValueAndDomainId(Setting::FIRST_ARTICLE_ON_HEADER_MENU_ARTICLE_ID, $domainId);
        $articleSettingData->secondArticleOnHeaderMenu = $this->articleFacade->findArticleBySettingValueAndDomainId(Setting::SECOND_ARTICLE_ON_HEADER_MENU_ARTICLE_ID, $domainId);
        $articleSettingData->thirdArticleOnHeaderMenu = $this->articleFacade->findArticleBySettingValueAndDomainId(Setting::THIRD_ARTICLE_ON_HEADER_MENU_ARTICLE_ID, $domainId);

        return $articleSettingData;
    }
}
