<?php

declare(strict_types=1);

namespace App\Twig;

use App\Model\Product\Flag\FlagFacade;
use App\Model\Product\Pricing\ProductPrice;
use Shopsys\ReadModelBundle\Flag\FlagsProvider;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FlagsExtension extends AbstractExtension
{
    public const DISCOUNT_DISPLAY_TYPE_PERCENTAGE = 'percentage';
    // the constant is used in Twig templates
    public const DISCOUNT_DISPLAY_TYPE_NOMINAL = 'nominal';

    /**
     * @var \Twig\Environment
     */
    protected $twigEnvironment;

    /**
     * @var \Shopsys\ReadModelBundle\Flag\FlagsProvider
     */
    protected $flagsProvider;

    /**
     * @var \App\Model\Product\Flag\FlagFacade
     */
    private FlagFacade $flagFacade;

    /**
     * @param \Shopsys\ReadModelBundle\Flag\FlagsProvider $flagsProvider
     * @param \Twig\Environment $twigEnvironment
     * @param \App\Model\Product\Flag\FlagFacade $flagFacade
     */
    public function __construct(
        FlagsProvider $flagsProvider,
        Environment $twigEnvironment,
        FlagFacade $flagFacade
    ) {
        $this->twigEnvironment = $twigEnvironment;
        $this->flagsProvider = $flagsProvider;
        $this->flagFacade = $flagFacade;
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
     * @param string $discountDisplayType
     * @return string
     */
    public function renderFlagsByIds(
        array $flagIds,
        string $classAddition = '',
        bool $onlyFirst = false,
        ?ProductPrice $productPrice = null,
        int $variantsCount = 0,
        string $discountDisplayType = self::DISCOUNT_DISPLAY_TYPE_PERCENTAGE
    ): string {
        $flags = $this->flagsProvider->getFlagsByIds($flagIds);
        $flags = $this->flagFacade->filterFlagsForList($flags);

        return $this->twigEnvironment->render(
            'Front/Inline/Product/productFlags.html.twig',
            [
                'flags' => $flags,
                'classAddition' => $classAddition,
                'onlyFirst' => $onlyFirst,
                'sellingPrice' => $productPrice,
                'variantsCount' => $variantsCount,
                'discountDisplayType' => $discountDisplayType,
            ]
        );
    }
}
