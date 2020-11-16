<?php

declare(strict_types=1);

namespace App\Twig;

use App\Model\Order\Item\OrderProductFacade;
use App\Model\Product\Availability\AvailabilityFacade;
use App\Model\Product\Flag\Flag;
use App\Model\Product\Flag\FlagFacade;
use App\Model\Product\Parameter\ParameterFacade;
use App\Model\Product\Parameter\ParameterValue;
use App\Model\Product\Pricing\ProductPrice;
use App\Model\Product\Product;
use App\Model\Product\ProductFacade;
use App\Model\Product\Set\ProductSet;
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
     * @var \App\Model\Product\ProductVisibilityRepository
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
     * @var bool
     */
    private $isFreeTransportFlagObtained = false;

    private AvailabilityFacade $availabilityFacade;

    private ?Flag $saleFlag = null;

    private OrderProductFacade $orderProductFacade;

    /**
     * ProductExtension constructor.
     *
     * @param \App\Model\Category\CategoryFacade $categoryFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductCachedAttributesFacade $productCachedAttributesFacade
     * @param \App\Model\Product\Parameter\ParameterFacade $parameterFacade
     * @param \App\Model\TransportAndPayment\FreeTransportAndPaymentFacade $freeTransportAndPaymentFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Model\Product\Flag\FlagFacade $flagFacade
     * @param \App\Model\Product\ProductFacade $productFacade
     * @param \App\Model\Product\Availability\AvailabilityFacade $availabilityFacade
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser $currentCustomerUser
     * @param \App\Model\Product\ProductVisibilityRepository $productVisibilityRepository
     * @param \App\Model\Order\Item\OrderProductFacade $orderProductFacade
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
        ProductVisibilityRepository $productVisibilityRepository,
        OrderProductFacade $orderProductFacade
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
        $this->orderProductFacade = $orderProductFacade;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
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
            new TwigFunction(
                'sortProductSetsByPrice',
                [$this, 'sortProductSetsByPrice']
            ),
            new TwigFunction(
                'getOrderProcessItemDeliveryDays',
                [$this, 'getOrderProcessItemDeliveryDays']
            ),
            new TwigFunction(
                'getAvailabilityText',
                [$this, 'getAvailabilityText']
            ),
            new TwigFunction(
                'cloneProductWithSubtractedStocks',
                [$this, 'cloneProductWithSubtractedStocks']
            ),
        ];
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
        $saleFlag = $this->getSaleFlag();
        // On FE, we do not want to display "clearance" flag at all, "sale" flag is used instead
        foreach ($productFlagsIndexedByPosition as $position => $flag) {
            if ($flag->isClearance()) {
                unset($productFlagsIndexedByPosition[$position]);
                $productFlagsIndexedByPosition[$saleFlag->getPosition()] = $saleFlag;
            }
        }
        $freeTransportFlag = $this->getDefaultFreeTransportFlag();
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
        if ($this->isFreeTransportFlagObtained === false) {
            $this->freeTransportFlag = $this->flagFacade->getDefaultFlagForFreeTransportAndPayment();
            $this->isFreeTransportFlagObtained = true;
        }

        return $this->freeTransportFlag;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @return string
     */
    public function getAvailabilityColor(Product $product): string
    {
        return $product->getCalculatedAvailability()->getRgbColor();
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

    /**
     * @param \App\Model\Product\Set\ProductSet[] $productSets
     * @return \App\Model\Product\Set\ProductSet[]
     */
    public function sortProductSetsByPrice(array $productSets): array
    {
        usort($productSets, function (ProductSet $productSet1, ProductSet $productSet2) {
            return (int)$this->productCachedAttributesFacade->getProductSellingPrice($productSet2->getItem())->getPriceWithVat()->subtract(
                $this->productCachedAttributesFacade->getProductSellingPrice($productSet1->getItem())->getPriceWithVat()
            )->getAmount();
        });

        return $productSets;
    }

    /**
     * @return \App\Model\Product\Flag\Flag
     */
    private function getSaleFlag(): Flag
    {
        if ($this->saleFlag === null) {
            $this->saleFlag = $this->flagFacade->getSaleFlag();
        }

        return $this->saleFlag;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param int $shoppingItemQuantity
     * @param bool $saleItem
     * @return string|null
     */
    public function getOrderProcessItemDeliveryDays(Product $product, int $shoppingItemQuantity, bool $saleItem): ?string
    {
        if (!$saleItem
            && !$product->isProductOnlyAtStoreStock(true)
            && $product->getDeliveryDays() !== null
            && $product->getRealInternalStockQuantity() < $shoppingItemQuantity
        ) {
            return $product->getDeliveryDays();
        }

        return null;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @return string
     */
    public function getAvailabilityText(Product $product): string
    {
        return $this->availabilityFacade->getAvailabilityText($product);
    }

    /**
     * Simulates the state of product's stocks and calculated availability as it would look without the quantity
     * that is currently in cart (minus one because we want to show the worst availability for current quantity
     * in cart and not for the next piece that could potentially get added)
     *
     * @param \App\Model\Product\Product $product
     * @param int $subtractQuantity
     * @return \App\Model\Product\Product
     */
    public function cloneProductWithSubtractedStocks(Product $product, int $subtractQuantity = 0): Product
    {
        $productClone = clone $product;
        $productClone->cloneStoreStocks();
        $this->orderProductFacade->subtractStockQuantity($productClone, $subtractQuantity - 1, false, false);
        $productClone->setCalculatedAvailability($this->availabilityFacade->getAvailability($productClone, true));

        return $productClone;
    }
}
