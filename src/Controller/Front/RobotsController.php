<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Component\Router\DomainRouterFactory;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Sitemap\SitemapFilePrefixer;
use Symfony\Component\HttpFoundation\Response;

class RobotsController extends FrontBaseController
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Sitemap\SitemapFilePrefixer
     */
    private $sitemapFilePrefixer;

    /**
     * @var string
     */
    private $sitemapsUrlPrefix;

    private DomainRouterFactory $domainRouterFactory;

    /**
     * @param string $sitemapsUrlPrefix
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\Sitemap\SitemapFilePrefixer $sitemapFilePrefixer
     * @param \App\Component\Router\DomainRouterFactory $domainRouterFactory
     */
    public function __construct(
        string $sitemapsUrlPrefix,
        Domain $domain,
        SitemapFilePrefixer $sitemapFilePrefixer,
        DomainRouterFactory $domainRouterFactory
    ) {
        $this->sitemapsUrlPrefix = $sitemapsUrlPrefix;
        $this->domain = $domain;
        $this->sitemapFilePrefixer = $sitemapFilePrefixer;
        $this->domainRouterFactory = $domainRouterFactory;
    }

    public function indexAction()
    {
        $sitemapFilePrefix = $this->sitemapFilePrefixer->getSitemapFilePrefixForDomain($this->domain->getId());

        $sitemapUrl = $this->domain->getUrl() . $this->sitemapsUrlPrefix . '/' . $sitemapFilePrefix . '.xml';

        $domainRouter = $this->domainRouterFactory->getRouter($this->domain->getId());
        $disallowedPaths = [];
        foreach ($this->getDisallowedRoutes() as $routeName => $parameters) {
            $disallowedPaths[] = $domainRouter->generate($routeName, $parameters);
        }

        $response = new Response();
        $response->headers->set('Content-Type', 'text/plain');

        return $this->render(
            'Front/robots.txt.twig',
            [
                'sitemapUrl' => $sitemapUrl,
                'disallowedPaths' => $disallowedPaths,
            ],
            $response
        );
    }

    /**
     * @return array
     */
    private function getDisallowedRoutes(): array
    {
        return [
            'front_cart' => [],
            'front_customer_edit' => [],
            'front_customer_orders' => [],
            'front_customer_order_detail_registered' => ['orderNumber' => '*'],
            'front_customer_order_detail_unregistered' => ['urlHash' => '*'],
            'front_login' => [],
            'front_logout' => ['_csrf_token' => '*'],
            'front_order_index' => [],
            'front_order_sent' => [],
            'front_order_paid' => ['urlHash' => '*'],
            'front_order_not_paid' => ['urlHash' => '*'],
            'front_order_repeat_gopay_payment' => ['urlHash' => '*'],
            'front_product_search' => [],
            'front_registration_register' => [],
            'front_registration_reset_password' => [],
            'front_registration_set_new_password' => [],
            'front_personal_data' => [],
            'front_personal_data_access' => ['hash' => '*'],
        ];
    }
}
