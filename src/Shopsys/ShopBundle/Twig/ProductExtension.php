<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Twig;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Category\CategoryFacade;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPrice;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\FrameworkBundle\Model\Product\ProductCachedAttributesFacade;
use Shopsys\ShopBundle\Model\Product\Flag\FlagFacade;
use Shopsys\ShopBundle\Model\Product\Parameter\ParameterFacade;
use Shopsys\ShopBundle\Model\Product\Parameter\ParameterValue;
use Shopsys\ShopBundle\Model\Product\ProductDistinguishingParameterValue;
use Shopsys\ShopBundle\Model\TransportAndPayment\FreeTransportAndPaymentFacade;
use Twig\TwigFunction;

class ProductExtension extends \Shopsys\FrameworkBundle\Twig\ProductExtension
{
    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductCachedAttributesFacade
     */
    protected $productCachedAttributesFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\Parameter\ParameterFacade
     */
    private $parameterFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\TransportAndPayment\FreeTransportAndPaymentFacade
     */
    private $freeTransportAndPaymentFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\Flag\FlagFacade
     */
    private $flagFacade;

    /**
     * ProductExtension constructor.
     * @param \Shopsys\FrameworkBundle\Model\Category\CategoryFacade $categoryFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductCachedAttributesFacade $productCachedAttributesFacade
     * @param \Shopsys\ShopBundle\Model\Product\Parameter\ParameterFacade $parameterFacade
     * @param \Shopsys\ShopBundle\Model\TransportAndPayment\FreeTransportAndPaymentFacade $freeTransportAndPaymentFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\ShopBundle\Model\Product\Flag\FlagFacade $flagFacade
     */
    public function __construct(
        CategoryFacade $categoryFacade,
        ProductCachedAttributesFacade $productCachedAttributesFacade,
        ParameterFacade $parameterFacade,
        FreeTransportAndPaymentFacade $freeTransportAndPaymentFacade,
        Domain $domain,
        FlagFacade $flagFacade
    ) {
        parent::__construct($categoryFacade, $productCachedAttributesFacade);

        $this->parameterFacade = $parameterFacade;
        $this->freeTransportAndPaymentFacade = $freeTransportAndPaymentFacade;
        $this->domain = $domain;
        $this->flagFacade = $flagFacade;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new TwigFunction(
                'distinguishingParameterValuesForProducts',
                [$this, 'findDistinguishingParameterValuesForProducts']
            ),
            new TwigFunction(
                'productDistinguishingParameterValue',
                [$this, 'getProductDistinguishingParameterValue']
            ),
            new TwigFunction(
                'getProductAdeptPrice',
                [$this, 'getProductAdeptPrice']
            ),
            new TwigFunction(
                'productParameters',
                [$this, 'getProductParameters']
            ),
            new TwigFunction(
                'getParameterValueById',
                [$this, 'getParameterValueById']
            ),
            new TwigFunction(
                'getProductFlagsWithFreeTransportAndPaymentFlag',
                [$this, 'getProductFlagsWithFreeTransportAndPaymentFlag']
            ),
        ];
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product[] $products
     * @return string[]
     */
    public function findDistinguishingParameterValuesForProducts(array $products): array
    {
        return $this->productCachedAttributesFacade->findDistinguishingParameterValuesForProducts($products);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @return \Shopsys\ShopBundle\Model\Product\ProductDistinguishingParameterValue
     */
    public function getProductDistinguishingParameterValue(Product $product): ProductDistinguishingParameterValue
    {
        return $this->productCachedAttributesFacade->getProductDistinguishingParameterValue($product);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @param string $productListDisplayName
     * @return string
     */
    protected function addParameterValueToProductDisplayName(
        Product $product,
        string $productListDisplayName
    ): string {
        $productDistinguishingParameterValue = $this->productCachedAttributesFacade->getProductDistinguishingParameterValue($product);

        if ($productDistinguishingParameterValue->getFirstDistinguishingParameterValue() !== null) {
            $productListDisplayName .= ' - ' . $productDistinguishingParameterValue->getFirstDistinguishingParameterValue();
        }

        if ($productDistinguishingParameterValue->getSecondDistinguishingParameterValue() !== null) {
            $productListDisplayName .= ' - ' . $productDistinguishingParameterValue->getSecondDistinguishingParameterValue();
        }

        return $productListDisplayName;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $product
     * @return \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPrice|null
     */
    public function getProductAdeptPrice(Product $product): ?ProductPrice
    {
        return $this->productCachedAttributesFacade->getProductAdeptPrice($product);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $product
     * @param string $locale
     * @return string
     */
    public function getProductParameters(Product $product, string $locale): string
    {
        $parametersForProduct = $this->productCachedAttributesFacade->getProductDistinguishingParameterValue($product, $locale);

        if ($parametersForProduct === null) {
            return '';
        }

        $parameters = [];

        if ($parametersForProduct->getFirstDistinguishingParameterValue() !== null) {
            $parameters[] = sprintf('%s: %s', $parametersForProduct->getFirstDistinguishingParameterName(), $parametersForProduct->getFirstDistinguishingParameterValue());
        }

        if ($parametersForProduct->getSecondDistinguishingParameterValue() !== null) {
            $parameters[] = sprintf('%s: %s', $parametersForProduct->getSecondDistinguishingParameterName(), $parametersForProduct->getSecondDistinguishingParameterValue());
        }

        if (count($parameters) === 0) {
            return '';
        }

        return sprintf('(%s)', implode(', ', $parameters));
    }

    /**
     * @param int $id
     * @return \Shopsys\ShopBundle\Model\Product\Parameter\ParameterValue
     */
    public function getParameterValueById(string $id): ParameterValue
    {
        return $this->parameterFacade->getParameterValueById((int)$id);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPrice $productPrice
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $product
     * @param int $limit
     * @return \Shopsys\ShopBundle\Model\Product\Flag\Flag[]
     */
    public function getProductFlagsWithFreeTransportAndPaymentFlag(ProductPrice $productPrice, Product $product, int $limit): array
    {
        $productFlagsIndexedByPosition = $product->getFlagsIndexedByPosition($limit);
        $freeTransportFlag = $this->flagFacade->getDefaultFlagForFreeTransportAndPayment();
        if ($freeTransportFlag !== null && $this->freeTransportAndPaymentFacade->isFree($productPrice->getPriceWithVat(), $this->domain->getId())) {
            $productFlagsIndexedByPosition[$freeTransportFlag->getPosition()] = $freeTransportFlag;
            sort($productFlagsIndexedByPosition);
            if ($limit !== null && count($productFlagsIndexedByPosition) > $limit) {
                array_pop($productFlagsIndexedByPosition); //remove last flags from array
            }
        }

        return $productFlagsIndexedByPosition;
    }
}
