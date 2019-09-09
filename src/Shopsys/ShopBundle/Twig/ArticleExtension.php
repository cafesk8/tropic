<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Twig;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\ShopBundle\Model\Article\Article;
use Shopsys\ShopBundle\Model\Article\ArticleFacade;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ArticleExtension extends AbstractExtension
{
    /**
     * @var \Shopsys\ShopBundle\Model\Article\ArticleFacade
     */
    private $articleFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @param \Shopsys\ShopBundle\Model\Article\ArticleFacade $articleFacade
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
        ];
    }

    /**
     * @param string $settingValue
     * @return \Shopsys\ShopBundle\Model\Article\Article|null
     */
    public function getArticleBySettingValue(string $settingValue): ?Article
    {
        return $this->articleFacade->findArticleBySettingValueAndDomainId($settingValue, $this->domain->getId());
    }
}
