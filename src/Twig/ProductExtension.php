<?php

declare(strict_types=1);

namespace App\Twig;

use App\Model\Product\Availability\AvailabilityData;
use App\Model\Product\Availability\AvailabilityFacade;
use App\Model\Product\Flag\Flag;
use App\Model\Product\Flag\FlagFacade;
use App\Model\Product\Parameter\ParameterFacade;
use App\Model\Product\Parameter\ParameterValue;
use App\Model\Product\ProductDistinguishingParameterValue;
use App\Model\Product\ProductFacade;
use App\Model\Product\View\ListedProductView;
use App\Model\TransportAndPayment\FreeTransportAndPaymentFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Category\CategoryFacade;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPrice;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\FrameworkBundle\Model\Product\ProductCachedAttributesFacade;
use Twig\TwigFunction;

class ProductExtension extends \Shopsys\FrameworkBundle\Twig\ProductExtension
{
    /**
     * @var \App\Model\Product\ProductCachedAttributesFacade
     */
    protected $productCachedAttributesFacade;

    /**
     * @var \App\Model\Product\Parameter\ParameterFacade
     */
    private $parameterFacade;

    /**
     * @var \App\Model\TransportAndPayment\FreeTransportAndPaymentFacade
     */
    private $freeTransportAndPaymentFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \App\Model\Product\Flag\FlagFacade
     */
    private $flagFacade;

    /**
     * @var \App\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @var \App\Model\Product\Flag\Flag|null
     */
    private $freeTransportFlag;

    /**
     * @var \App\Model\Product\Availability\AvailabilityFacade
     */
    private $availabilityFacade;

    /**
     * @var string[]
     */
    private $availabilityColorsIndexedByName;

    /**
     * ProductExtension constructor.
     *
     * @param \Shopsys\FrameworkBundle\Model\Category\CategoryFacade $categoryFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductCachedAttributesFacade $productCachedAttributesFacade
     * @param \App\Model\Product\Parameter\ParameterFacade $parameterFacade
     * @param \App\Model\TransportAndPayment\FreeTransportAndPaymentFacade $freeTransportAndPaymentFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Model\Product\Flag\FlagFacade $flagFacade
     * @param \App\Model\Product\ProductFacade $productFacade
     * @param \App\Model\Product\Availability\AvailabilityFacade $availabilityFacade
     */
    public function __construct(
        CategoryFacade $categoryFacade,
        ProductCachedAttributesFacade $productCachedAttributesFacade,
        ParameterFacade $parameterFacade,
        FreeTransportAndPaymentFacade $freeTransportAndPaymentFacade,
        Domain $domain,
        FlagFacade $flagFacade,
        ProductFacade $productFacade,
        AvailabilityFacade $availabilityFacade
    ) {
        parent::__construct($categoryFacade, $productCachedAttributesFacade);

        $this->parameterFacade = $parameterFacade;
        $this->freeTransportAndPaymentFacade = $freeTransportAndPaymentFacade;
        $this->domain = $domain;
        $this->flagFacade = $flagFacade;
        $this->productFacade = $productFacade;
        $this->availabilityFacade = $availabilityFacade;
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
                'getProductRegisteredCustomerPrice',
                [$this, 'getProductRegisteredCustomerPrice']
            ),
            new TwigFunction(
                'getParameterValueById',
                [$this, 'getParameterValueById']
            ),
            new TwigFunction(
                'getProductFlagsWithFreeTransportAndPaymentFlag',
                [$this, 'getProductFlagsWithFreeTransportAndPaymentFlag']
            ),
            new TwigFunction(
                'getProductById',
                [$this, 'getProductById']
            ),
            new TwigFunction(
                'getFreeTransportAndPaymentFlagIdIfShouldBeDisplayed',
                [$this, 'getFreeTransportAndPaymentFlagIdIfShouldBeDisplayed']
            ),
            new TwigFunction(
                'getAvailabilityColor',
                [$this, 'getAvailabilityColor']
            ),
        ];
    }

    /**
     * @param \App\Model\Product\Product[] $products
     * @return string[]
     */
    public function findDistinguishingParameterValuesForProducts(array $products): array
    {
        return $this->productCachedAttributesFacade->findDistinguishingParameterValuesForProducts($products);
    }

    /**
     * @param \App\Model\Product\Product $product
     * @return \App\Model\Product\ProductDistinguishingParameterValue
     */
    public function getProductDistinguishingParameterValue(Product $product): ProductDistinguishingParameterValue
    {
        return $this->productCachedAttributesFacade->getProductDistinguishingParameterValue($product);
    }

    /**
     * @param \App\Model\Product\Product $product
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
     * @param \App\Model\Product\Product $product
     * @return \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPrice|null
     */
    public function getProductRegisteredCustomerPrice(Product $product): ?ProductPrice
    {
        return $this->productCachedAttributesFacade->getProductRegisteredCustomerPrice($product);
    }

    /**
     * @param string $id
     * @return \App\Model\Product\Parameter\ParameterValue
     */
    public function getParameterValueById(string $id): ParameterValue
    {
        return $this->parameterFacade->getParameterValueById((int)$id);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPrice $productPrice
     * @param \App\Model\Product\Product $product
     * @param int $limit
     * @return \App\Model\Product\Flag\Flag[]
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

    /**
     * @param int $productId
     * @return \App\Model\Product\Product
     */
    public function getProductById(int $productId): Product
    {
        return $this->productFacade->getById($productId);
    }

    /**
     * @param \App\Model\Product\View\ListedProductView $listedProductView
     * @return int|null
     */
    public function getFreeTransportAndPaymentFlagIdIfShouldBeDisplayed(ListedProductView $listedProductView): ?int
    {
        $freeTransportFlag = $this->getDefaultFreeTransportFlag();
        if ($freeTransportFlag !== null && $this->freeTransportAndPaymentFacade->isFree($listedProductView->getSellingPrice()->getPriceWithVat(), $this->domain->getId())) {
            return $freeTransportFlag->getId();
        }

        return null;
    }

    /**
     * @return \App\Model\Product\Flag\Flag|null
     */
    private function getDefaultFreeTransportFlag(): ?Flag
    {
        if ($this->freeTransportFlag !== null) {
            return $this->flagFacade->getDefaultFlagForFreeTransportAndPayment();
        }

        return $this->freeTransportFlag;
    }

    /**
     * @param string $availability
     * @return string
     */
    public function getAvailabilityColor(string $availability): string
    {
        if ($this->availabilityColorsIndexedByName === null) {
            $this->availabilityColorsIndexedByName = $this->availabilityFacade->getColorsIndexedByName();
        }

        return $this->availabilityColorsIndexedByName[$availability] ?? AvailabilityData::DEFAULT_COLOR;
    }
}
