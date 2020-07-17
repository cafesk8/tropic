<?php

declare(strict_types=1);

namespace App\Component\Router\PrettyFilterUrl;

use App\Form\Front\Product\ProductFilterFormType;
use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrl;
use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlGenerator;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCompiler;

class PrettyFilterUrlGenerator extends FriendlyUrlGenerator
{
    /**
     * @inheritDoc
     */
    public function getGeneratedUrl($routeName, Route $route, FriendlyUrl $friendlyUrl, array $parameters, $referenceType)
    {
        $tokens = [];

        if (isset($parameters[ProductFilterFormType::NAME][ProductFilterFormType::FIELD_PARAMETERS]) &&
            count($parameters[ProductFilterFormType::NAME][ProductFilterFormType::FIELD_PARAMETERS]) > 2) {
            $tokens[] = ['text', 'ni/'];
        }

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
}
