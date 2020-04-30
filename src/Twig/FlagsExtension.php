<?php

declare(strict_types=1);

namespace App\Twig;

use App\Model\Product\Pricing\ProductPrice;
use Shopsys\ReadModelBundle\Flag\FlagsProvider;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FlagsExtension extends AbstractExtension
{
    /**
     * @var \Twig\Environment
     */
    protected $twigEnvironment;

    /**
     * @var \Shopsys\ReadModelBundle\Flag\FlagsProvider
     */
    protected $flagsProvider;

    /**
     * @param \Shopsys\ReadModelBundle\Flag\FlagsProvider $flagsProvider
     * @param \Twig\Environment $twigEnvironment
     */
    public function __construct(
        FlagsProvider $flagsProvider,
        Environment $twigEnvironment
    ) {
        $this->twigEnvironment = $twigEnvironment;
        $this->flagsProvider = $flagsProvider;
    }

    /**
     * @return \Twig\TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('renderFlagsByIds', [$this, 'renderFlagsByIds'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * @param int[] $flagIds
     * @param string $classAddition
     * @param bool $onlyFirst
     * @param \App\Model\Product\Pricing\ProductPrice|null $productPrice
     * @param int $variantsCount
     * @return string
     */
    public function renderFlagsByIds(
        array $flagIds,
        string $classAddition = '',
        bool $onlyFirst = false,
        ?ProductPrice $productPrice = null,
        int $variantsCount = 0
    ): string {
        return $this->twigEnvironment->render(
            'Front/Inline/Product/productFlags.html.twig',
            [
                'flags' => $this->flagsProvider->getFlagsByIds($flagIds),
                'classAddition' => $classAddition,
                'onlyFirst' => $onlyFirst,
                'sellingPrice' => $productPrice,
                'variantsCount' => $variantsCount,
            ]
        );
    }
}
