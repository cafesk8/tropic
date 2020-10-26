<?php

declare(strict_types=1);

namespace App\Twig;

use App\Model\Article\Article;
use App\Model\Article\ArticleFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ArticleExtension extends AbstractExtension
{
    /**
     * @var \App\Model\Article\ArticleFacade
     */
    private $articleFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @param \App\Model\Article\ArticleFacade $articleFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(ArticleFacade $articleFacade, Domain $domain)
    {
        $this->articleFacade = $articleFacade;
        $this->domain = $domain;
    }

    /**
     * @inheritDoc
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('getArticleBySettingValue', [$this, 'getArticleBySettingValue']),
            new TwigFunction('findVisibleArticleBySettingValue', [$this, 'findVisibleArticleBySettingValue']),
        ];
    }

    /**
     * @param string $settingValue
     * @return \App\Model\Article\Article|null
     */
    public function getArticleBySettingValue(string $settingValue): ?Article
    {
        return $this->articleFacade->findArticleBySettingValueAndDomainId($settingValue, $this->domain->getId());
    }

    /**
     * @param string $settingValue
     * @return \App\Model\Article\Article|null
     */
    public function findVisibleArticleBySettingValue(string $settingValue): ?Article
    {
        return $this->articleFacade->findVisibleArticleBySettingValueAndDomainId($settingValue, $this->domain->getId());
    }
}
