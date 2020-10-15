<?php

declare(strict_types=1);

namespace App\Twig;

use App\Component\Domain\DomainHelper;
use App\Model\Heureka\HeurekaReviewFacade;
use Shopsys\FrameworkBundle\Twig\ShopInfoExtension as BaseShopInfoExtension;
use Twig\TwigFunction;

class ShopInfoExtension extends BaseShopInfoExtension
{
    /**
     * @inheritDoc
     */
    public function getFunctions(): array
    {
        $functions = parent::getFunctions();
        $functions[] = new TwigFunction('getHeurekaReviewsUrl', [$this, 'getHeurekaReviewsUrl']);

        return $functions;
    }

    /**
     * @return string
     */
    public function getHeurekaReviewsUrl(): string
    {
        $domainId = $this->domain->getId();

        if (isset(HeurekaReviewFacade::HEUREKA_REVIEWS_URLS[$domainId])) {
            return HeurekaReviewFacade::HEUREKA_REVIEWS_URLS[$domainId];
        }

        return HeurekaReviewFacade::HEUREKA_REVIEWS_URLS[DomainHelper::CZECH_DOMAIN];
    }
}