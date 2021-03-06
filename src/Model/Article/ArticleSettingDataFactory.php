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

        $articleSettingData->productSizeArticle = $this->articleFacade->findArticleBySettingValueAndDomainId(Setting::PRODUCT_SIZE_ARTICLE_ID, $domainId);
        $articleSettingData->loyaltyProgramArticle = $this->articleFacade->findArticleBySettingValueAndDomainId(Setting::LOYALTY_PROGRAM_ARTICLE_ID, $domainId);

        return $articleSettingData;
    }
}
