<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\BushmanClub;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Setting\Setting as BaseSetting;
use Shopsys\FrameworkBundle\Model\Article\Article;
use Shopsys\FrameworkBundle\Model\Article\ArticleFacade;
use Shopsys\ShopBundle\Component\Setting\Setting;

class BushmanClubFacade
{
    /**
     * @var \Shopsys\FrameworkBundle\Model\Article\ArticleFacade
     */
    private $articleFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Setting\Setting
     */
    private $setting;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Article\ArticleFacade $articleFacade
     * @param \Shopsys\FrameworkBundle\Component\Setting\Setting $setting
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(
        ArticleFacade $articleFacade,
        BaseSetting $setting,
        Domain $domain
    ) {
        $this->articleFacade = $articleFacade;
        $this->setting = $setting;
        $this->domain = $domain;
    }

    /**
     * @param int $domainId
     * @return \Shopsys\FrameworkBundle\Model\Article\Article|null
     */
    public function findBushmanClubArticleByDomainId(int $domainId): ?Article
    {
        $bushmanClubArticleId = $this->setting->getForDomain(Setting::BUSHMAN_CLUB_ARTICLE_ID, $domainId);

        if ($bushmanClubArticleId !== null) {
            return $this->articleFacade->findById($bushmanClubArticleId);
        }

        return null;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Article\Article|null $bushmanClubArticle
     * @param int $domainId
     */
    public function setBushmanClubArticleOnDomain(?Article $bushmanClubArticle, int $domainId): void
    {
        $bushmanClubArticleId = null;
        if ($bushmanClubArticle !== null) {
            $bushmanClubArticleId = $bushmanClubArticle->getId();
        }
        $this->setting->setForDomain(
            Setting::BUSHMAN_CLUB_ARTICLE_ID,
            $bushmanClubArticleId,
            $domainId
        );
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Article\Article $article
     * @return bool
     */
    public function isArticleUsedForBushmanClub(Article $article): bool
    {
        foreach ($this->domain->getAll() as $domainConfig) {
            if ($this->findBushmanClubArticleByDomainId($domainConfig->getId()) === $article) {
                return true;
            }
        }

        return false;
    }
}
