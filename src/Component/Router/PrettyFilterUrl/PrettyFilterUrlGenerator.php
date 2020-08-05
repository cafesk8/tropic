<?php

declare(strict_types=1);

namespace App\Component\Router\PrettyFilterUrl;

use App\Form\Front\Product\ProductFilterFormType;
use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrl;
use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlGenerator;
use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlRepository;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCompiler;

class PrettyFilterUrlGenerator extends FriendlyUrlGenerator
{
    private PrettyFilterUrlFacade $prettyFilterUrlFacade;

    /**
     * @param \Symfony\Component\Routing\RequestContext $context
     * @param \App\Component\Router\FriendlyUrl\FriendlyUrlRepository $friendlyUrlRepository
     * @param \App\Component\Router\PrettyFilterUrl\PrettyFilterUrlFacade $prettyFilterUrlFacade
     */
    public function __construct(RequestContext $context, FriendlyUrlRepository $friendlyUrlRepository, PrettyFilterUrlFacade $prettyFilterUrlFacade)
    {
        parent::__construct($context, $friendlyUrlRepository);
        $this->prettyFilterUrlFacade = $prettyFilterUrlFacade;
    }

    /**
     * @inheritDoc
     */
    public function getGeneratedUrl($routeName, Route $route, FriendlyUrl $friendlyUrl, array $parameters, $referenceType)
    {
        $tokens = [];
        $parameters = $this->processBrandFilters($parameters);
        $parameters = $this->removeEmptyFilters($parameters);

        $tokens[] = ['text', '/' . $friendlyUrl->getSlug()];
        $compiledRoute = RouteCompiler::compile($route);

        return $this->doGenerate(
            $compiledRoute->getVariables(),
            $route->getDefaults(),
            $route->getRequirements(),
            $tokens,
            $parameters,
            $routeName,
            $referenceType,
            $compiledRoute->getHostTokens(),
            $route->getSchemes()
        );
    }

    /**
     * @param array $parameters
     * @return array
     */
    private function processBrandFilters(array $parameters): array
    {
        $filters = $parameters[ProductFilterFormType::NAME] ?? [];

        if (isset($filters[ProductFilterFormType::FIELD_BRANDS])) {
            $brandSlugs = $this->prettyFilterUrlFacade->getBrandSlugsByIds($filters[ProductFilterFormType::FIELD_BRANDS]);

            if (!empty($brandSlugs)) {
                $parameters[PrettyFilterUrlRouter::BRAND_PARAM_NAME] = implode(',', $brandSlugs);
                unset($parameters[ProductFilterFormType::NAME][ProductFilterFormType::FIELD_BRANDS]);
            }
        }

        return $parameters;
    }

    /**
     * @param array $parameters
     * @return array
     */
    private function removeEmptyFilters(array $parameters): array
    {
        $filters = $parameters[ProductFilterFormType::NAME] ?? [];

        foreach ($filters as $key => $values) {
            if (empty($values) && !is_numeric($values) && !is_bool($values)) {
                unset($filters[$key]);
            }
        }

        if (count($filters) === 0) {
            unset($parameters[ProductFilterFormType::NAME]);
        } else {
            $parameters[ProductFilterFormType::NAME] = $filters;
        }

        return $parameters;
    }
}
