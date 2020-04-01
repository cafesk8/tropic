<?php

declare(strict_types=1);

namespace App\Twig;

use App\Model\Product\Availability\AvailabilityData;
use App\Model\Product\Availability\AvailabilityFacade;
use App\Model\Product\Flag\Flag;
use App\Model\Product\Flag\FlagFacade;
use App\Model\Product\Parameter\ParameterFacade;
use App\Model\Product\Parameter\ParameterValue;
use App\Model\Product\Pricing\ProductPrice;
use App\Model\Product\Product;
use App\Model\Product\ProductFacade;
use App\Model\TransportAndPayment\FreeTransportAndPaymentFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Category\CategoryFacade;
use Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser;
use Shopsys\FrameworkBundle\Model\Product\ProductCachedAttributesFacade;
use Shopsys\FrameworkBundle\Model\Product\ProductVisibilityRepository;
use Shopsys\ReadModelBundle\Product\Listed\ListedProductView;
use Twig\TwigFunction;

class ProductExtension extends \Shopsys\FrameworkBundle\Twig\ProductExtension
{
    /**
     * @var \App\Model\Product\ProductCachedAttributesFacade
     */
    protected $productCachedAttributesFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser
     */
    protected $currentCustomerUser;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\ProductVisibilityRepository
     */
    protected $productVisibilityRepository;

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
     * @param \App\Model\Category\CategoryFacade $categoryFacade
     * @param \App\Model\Product\ProductCachedAttributesFacade $productCachedAttributesFacade
     * @param \App\Model\Product\Parameter\ParameterFacade $parameterFacade
     * @param \App\Model\TransportAndPayment\FreeTransportAndPaymentFacade $freeTransportAndPaymentFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Model\Product\Flag\FlagFacade $flagFacade
     * @param \App\Model\Product\ProductFacade $productFacade
     * @param \App\Model\Product\Availability\AvailabilityFacade $availabilityFacade
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser $currentCustomerUser
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductVisibilityRepository $productVisibilityRepository
     */
    public function __construct(
        CategoryFacade $categoryFacade,
        ProductCachedAttributesFacade $productCachedAttributesFacade,
        ParameterFacade $parameterFacade,
        FreeTransportAndPaymentFacade $freeTransportAndPaymentFacade,
        Domain $domain,
        FlagFacade $flagFacade,
        ProductFacade $productFacade,
        AvailabilityFacade $availabilityFacade,
        CurrentCustomerUser $currentCustomerUser,
        ProductVisibilityRepository $productVisibilityRepository
    ) {
        parent::__construct($categoryFacade, $productCachedAttributesFacade);

        $this->parameterFacade = $parameterFacade;
        $this->freeTransportAndPaymentFacade = $freeTransportAndPaymentFacade;
        $this->domain = $domain;
        $this->flagFacade = $flagFacade;
        $this->productFacade = $productFacade;
        $this->availabilityFacade = $availabilityFacade;
        $this->currentCustomerUser = $currentCustomerUser;
        $this->productVisibilityRepository = $productVisibilityRepository;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new TwigFunction(
                'getDefaultPrice',
                [$this, 'getDefaultPrice']
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
            new TwigFunction(
                'filterOnlyVisibleVariants',
                [$this, 'filterOnlyVisibleVariants']
            ),
        ];
    }

    /**
     * @param \App\Model\Product\Product $product
     * @return \App\Model\Product\Pricing\ProductPrice
     */
    public function getDefaultPrice(Product $product): ProductPrice
    {
        return $this->productCachedAttributesFacade->getDefaultPrice($product);
    }

    /**
     * @param \App\Model\Product\Product $product
     * @return \App\Model\Product\Pricing\ProductPrice|null
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
     * @param \App\Model\Product\Pricing\ProductPrice $productPrice
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
     * @param \Shopsys\ReadModelBundle\Product\Listed\ListedProductView $listedProductView
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

    /**
     * @param \App\Model\Product\Product $product
     * @return \App\Model\Product\Product[]
     */
    public function filterOnlyVisibleVariants(Product $product): array
    {
        $domainId = $this->domain->getId();
        $pricingGroup = $this->currentCustomerUser->getPricingGroup();
        return array_filter($product->getVariants(), function (Product $variant) use ($domainId, $pricingGroup) {
            $productVisibility = $this->productVisibilityRepository->getProductVisibility(
                $variant,
                $pricingGroup,
                $domainId
            );
            return $productVisibility->isVisible();
        });
    }
}
