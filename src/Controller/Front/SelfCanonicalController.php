<?php

declare(strict_types=1);

namespace App\Controller\Front;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class SelfCanonicalController extends FrontBaseController
{

    private const SELF_CANONICAL_PAGES = [
        'front_homepage',
        'front_product_list',
        'front_product_detail',
        'front_sale_product_list',
        'front_article_detail',
        'front_news_product_list',
        'front_blogarticle_detail',
        'front_brand_detail',
        'front_blogcategory_detail',
    ];

    protected RequestStack $requestStack;

    /**
     * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getSelfCanonicalLink(Request $request): Response
    {
        $masterRequest = $this->requestStack->getMasterRequest();
        $routeName = $masterRequest->attributes->get('_route');
        $isSelfCanonicalNeeded = $this->isSelfCanonicalNeeded($request);

        if (in_array($routeName, self::SELF_CANONICAL_PAGES, true) === true && $isSelfCanonicalNeeded) {
            $selfCanonicalLink = $this->generateUrl($routeName, ['id' => $masterRequest->attributes->get('id')], 0);
        } else {
            $selfCanonicalLink = null;
        }

        return $this->render('Front/Inline/SelfCanonical/selfCanonicalLink.html.twig', [
            'selfCanonicalLink' => $selfCanonicalLink,
        ]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return bool
     */
    private function isSelfCanonicalNeeded(Request $request): bool
    {
        $requestParameters = $request->query->all();
        $requestParametersKeys = array_keys($requestParameters);

        if (!isset($requestParameters['page'])) {
            if (count($requestParametersKeys) > 1
                || (!empty($requestParametersKeys)
                    && !$this->inArrayAny(['product_filter_form', 'vyrobce'], $requestParametersKeys))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array $needles
     * @param array $haystack
     * @return bool
     */
    private function inArrayAny(array $needles, array $haystack): bool
    {
	    return !empty(array_intersect($needles, $haystack));
    }
}
