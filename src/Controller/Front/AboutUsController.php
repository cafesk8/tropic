<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Component\Setting\Setting;
use App\Model\Article\ArticleFacade;
use App\Model\Heureka\HeurekaReviewFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Symfony\Component\HttpFoundation\Response;

class AboutUsController extends FrontBaseController
{
    /**
     * @var \App\Model\Article\ArticleFacade
     */
    private $articleFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    private HeurekaReviewFacade $heurekaReviewFacade;

    /**
     * @param \App\Model\Article\ArticleFacade $articleFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Model\Heureka\HeurekaReviewFacade $heurekaReviewFacade
     */
    public function __construct(ArticleFacade $articleFacade, Domain $domain, HeurekaReviewFacade $heurekaReviewFacade)
    {
        $this->articleFacade = $articleFacade;
        $this->domain = $domain;
        $this->heurekaReviewFacade = $heurekaReviewFacade;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function infoAction(): Response
    {
        $loyaltyProgramArticle = $this->articleFacade->findArticleBySettingValueAndDomainId(
            Setting::LOYALTY_PROGRAM_ARTICLE_ID,
            $this->domain->getId()
        );

        return $this->render('Front/Content/AboutUs/info.html.twig', [
            'loyaltyProgramArticle' => $loyaltyProgramArticle,
            'heurekaReviews' => $this->heurekaReviewFacade->getLatestReviews(),
        ]);
    }
}
