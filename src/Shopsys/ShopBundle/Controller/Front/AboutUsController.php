<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Front;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\ShopBundle\Component\Setting\Setting;
use Shopsys\ShopBundle\Model\Article\ArticleFacade;
use Symfony\Component\HttpFoundation\Response;

class AboutUsController extends FrontBaseController
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
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function infoAction(): Response
    {
        $bushmanClubArticle = $this->articleFacade->findArticleBySettingValueAndDomainId(
            Setting::BUSHMAN_CLUB_ARTICLE_ID,
            $this->domain->getId()
        );

        return $this->render('@ShopsysShop/Front/Content/AboutUs/info.html.twig', [
            'bushmanClubArticle' => $bushmanClubArticle,
        ]);
    }
}
