<?php

declare(strict_types=1);

namespace App\Model\Gtm;

use App\Model\Cart\Item\CartItem;
use App\Model\Category\Category;
use App\Model\Category\CategoryFacade;
use App\Model\Customer\User\CustomerUser as Customer;
use App\Model\Gtm\Data\DataLayerPage;
use App\Model\Gtm\Data\DataLayerProduct;
use App\Model\Gtm\Data\DataLayerUser;
use App\Model\Order\Item\OrderItem;
use App\Model\Order\Item\QuantifiedProduct;
use App\Model\Order\Order;
use App\Model\Order\Preview\OrderPreview;
use App\Model\Product\Flag\Flag;
use App\Model\Product\Flag\FlagFacade;
use App\Model\Product\Product;
use App\Model\Product\ProductCachedAttributesFacade;
use App\Model\Product\ProductOnCurrentDomainElasticFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Administrator\Security\AdministratorFrontSecurityFacade;

class DataLayerMapper
{
    private const PRICE_SCALE = 3;

    private const ROUTE_NAMES_TO_PAGE_TYPE = [
        'front_homepage' => DataLayerPage::TYPE_HOME,
        'front_article_detail' => DataLayerPage::TYPE_ARTICLE,
        'front_article_list' => DataLayerPage::TYPE_BLOG,
        'front_blogcategory_detail' => DataLayerPage::TYPE_BLOG,
        'front_blogarticle_detail' => DataLayerPage::TYPE_BLOG_ARTICLE,
        'front_cart' => DataLayerPage::TYPE_CART,
        'front_brand_list' => DataLayerPage::TYPE_BRAND,
        'front_brand_detail' => DataLayerPage::TYPE_BRAND,
        'front_product_detail' => DataLayerPage::TYPE_PRODUCT,
        'front_order_sent' => DataLayerPage::TYPE_PURCHASE,
        'front_order_paid' => DataLayerPage::TYPE_PURCHASE,
        'front_product_search' => DataLayerPage::TYPE_SEARCH,
        'front_product_list' => DataLayerPage::TYPE_CATEGORY,
        'front_precart' => DataLayerPage::TYPE_PRECART,
        'front_order_not_paid' => DataLayerPage::TYPE_PURCHASE_FAIL,
        'front_about_us_info' => DataLayerPage::TYPE_ABOUT_US,
        'front_customer_orders' => DataLayerPage::TYPE_ORDERS_LIST,
        'front_customer_order_detail_registered' => DataLayerPage::TYPE_ORDER_DETAIL,
        'front_customer_order_detail_unregistered' => DataLayerPage::TYPE_ORDER_DETAIL,
        'front_order_repeat_gopay_payment' => DataLayerPage::TYPE_PAYMENT_REPEAT,
        'front_sale_product_list' => DataLayerPage::TYPE_CATEGORY,
        'front_news_product_list' => DataLayerPage::TYPE_CATEGORY,
    ];

    private AdministratorFrontSecurityFacade $administratorFrontSecurityFacade;

    private CategoryFacade $categoryFacade;

    private ProductCachedAttributesFacade $productCachedAttributesFacade;

    private GtmHelper $gtmHelper;

    private Domain $domain;

    private FlagFacade $flagFacade;

    private ProductOnCurrentDomainElasticFacade $productOnCurrentDomainElasticFacade;

    /**
     * @param \App\Model\Category\CategoryFacade $categoryFacade
     * @param \App\Model\Product\ProductCachedAttributesFacade $productCachedAttributesFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Model\Gtm\GtmHelper $gtmHelper
     * @param \Shopsys\FrameworkBundle\Model\Administrator\Security\AdministratorFrontSecurityFacade $administratorFrontSecurityFacade
     * @param \App\Model\Product\Flag\FlagFacade $flagFacade
     * @param \App\Model\Product\ProductOnCurrentDomainElasticFacade $productOnCurrentDomainElasticFacade
     */
    public function __construct(
        CategoryFacade $categoryFacade,
        ProductCachedAttributesFacade $productCachedAttributesFacade,
        Domain $domain,
        GtmHelper $gtmHelper,
        AdministratorFrontSecurityFacade $administratorFrontSecurityFacade,
        FlagFacade $flagFacade,
        ProductOnCurrentDomainElasticFacade $productOnCurrentDomainElasticFacade
    ) {
        $this->categoryFacade = $categoryFacade;
        $this->productCachedAttributesFacade = $productCachedAttributesFacade;
        $this->domain = $domain;
        $this->gtmHelper = $gtmHelper;
        $this->administratorFrontSecurityFacade = $administratorFrontSecurityFacade;
        $this->flagFacade = $flagFacade;
        $this->productOnCurrentDomainElasticFacade = $productOnCurrentDomainElasticFacade;
    }

    /**
     * @param string $routeName
     * @param \App\Model\Gtm\Data\DataLayerPage $dataLayerPage
     */
    public function mapRouteNameToDataLayerPage($routeName, DataLayerPage $dataLayerPage): void
    {
        $dataLayerPage->setType(
            self::ROUTE_NAMES_TO_PAGE_TYPE[$routeName] ?? DataLayerPage::TYPE_OTHER
        );
    }

    /**
     * @param \App\Model\Customer\User\CustomerUser|null $currentCustomerUser
     * @param \App\Model\Gtm\Data\DataLayerUser $dataLayerUser
     */
    public function mapCurrentCustomerToDataLayerUser(?Customer $currentCustomerUser, DataLayerUser $dataLayerUser): void
    {
        if ($currentCustomerUser !== null) {
            $dataLayerUser->setId((string)$currentCustomerUser->getId());
            $dataLayerUser->setState(DataLayerUser::STATE_LOGGED_IN);

            if ($this->administratorFrontSecurityFacade->isAdministratorLoggedAsCustomer()) {
                $dataLayerUser->setType(DataLayerUser::TYPE_ADMIN);
            } else {
                $dataLayerUser->setType(DataLayerUser::TYPE_CUSTOMER);
            }
        } else {
            $dataLayerUser->setState(DataLayerUser::STATE_ANONYMOUS);
            $dataLayerUser->setType(DataLayerUser::TYPE_VISITOR);
        }
    }

    /**
     * @param \App\Model\Category\Category $category
     * @param \App\Model\Gtm\Data\DataLayerPage $dataLayerPage
     * @param string $locale
     */
    public function mapCategoryToDataLayerPage(Category $category, DataLayerPage $dataLayerPage, string $locale): void
    {
        $this->mapCategoryToDataLayerPageCategory($category, $dataLayerPage, $locale);

        if ($category->isPreListingCategory()) {
            $dataLayerPage->setType(DataLayerPage::TYPE_CATEGORY_PRELIST);
        }
    }

    /**
     * @param \App\Model\Category\Category $category
     * @param \App\Model\Gtm\Data\DataLayerPage $dataLayerPage
     * @param string $locale
     */
    private function mapCategoryToDataLayerPageCategory(Category $category, DataLayerPage $dataLayerPage, string $locale): void
    {
        $categoriesInPath = $this->categoryFacade->getCategoriesInPath($category);
        $categoriesIdsInPath = [];
        $categoriesNamesInPath = [];
        foreach ($categoriesInPath as $categoryInPath) {
            $categoriesIdsInPath[] = (string)$categoryInPath->getId();
            $categoriesNamesInPath[] = $categoryInPath->getName($locale);
        }

        $dataLayerPage->setCategoryId($categoriesIdsInPath);
        $dataLayerPage->setCategory($categoriesNamesInPath);
        $dataLayerPage->setCategoryLevel((string)$category->getLevel());
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param \App\Model\Gtm\Data\DataLayerPage $dataLayerPage
     * @param string $locale
     */
    public function mapProductToDataLayerPage(Product $product, DataLayerPage $dataLayerPage, string $locale): void
    {
        $productMainCategory = $this->categoryFacade->getProductMainCategoryByDomainId($product, Domain::MAIN_ADMIN_DOMAIN_ID);

        $this->mapCategoryToDataLayerPageCategory($productMainCategory, $dataLayerPage, $locale);
    }

    /**
     * @param \App\Model\Order\Preview\OrderPreview $orderPreview
     * @param string $locale
     * @return \App\Model\Gtm\Data\DataLayerProduct[]
     */
    public function createDataLayerProductsFromOrderPreview(OrderPreview $orderPreview, string $locale): array
    {
        $quantifiedProducts = $orderPreview->getQuantifiedProducts();
        $gifts = $orderPreview->getGifts();
        $orderGift = $orderPreview->getOrderGiftProduct();
        $dataLayerProducts = [];

        $itemIds = array_map(fn (QuantifiedProduct $quantifiedProduct) => ($quantifiedProduct->getProduct()->getId()), $quantifiedProducts);
        $giftIds = array_map(fn (CartItem $gift) => ($gift->getProduct()->getId()), $gifts);
        $allIds = array_merge($itemIds, $giftIds);
        if ($orderGift !== null) {
            $allIds[] = $orderGift->getId();
        }

        $productsDataIndexedByProductId = $this->getElasticProductsDataIndexedById($allIds);

        foreach ($quantifiedProducts as $quantifiedProduct) {
            /** @var \App\Model\Product\Product $product */
            $product = $quantifiedProduct->getProduct();
            $quantity = $quantifiedProduct->getQuantity();
            $categoryPath = $productsDataIndexedByProductId[$product->getId()]['main_category_path'] ?? '';

            $dataLayerProduct = new DataLayerProduct();
            $this->mapProductToDataLayerProduct($product, $dataLayerProduct, $locale, false, $quantifiedProduct->isSaleItem(), $categoryPath);
            $dataLayerProduct->setQuantity($quantity);
            $dataLayerProducts[] = $dataLayerProduct;
        }

        foreach ($gifts as $gift) {
            $productGift = $gift->getProduct();
            $categoryPath = $productsDataIndexedByProductId[$productGift->getId()]['main_category_path'] ?? '';
            $dataLayerProduct = new DataLayerProduct();
            $this->mapProductToDataLayerProduct($productGift, $dataLayerProduct, $locale, true, null, $categoryPath);
            $dataLayerProduct->setQuantity($gift->getQuantity());
            $dataLayerProducts[] = $dataLayerProduct;
        }

        if ($orderGift !== null) {
            $categoryPath = $productsDataIndexedByProductId[$orderGift->getId()]['main_category_path'] ?? '';
            $dataLayerProduct = new DataLayerProduct();
            $this->mapProductToDataLayerProduct($orderGift, $dataLayerProduct, $locale, true, null, $categoryPath);
            $dataLayerProduct->setQuantity(1);
            $dataLayerProducts[] = $dataLayerProduct;
        }

        return $dataLayerProducts;
    }

    /**
     * @param \App\Model\Product\Product[] $products
     * @param string $locale
     * @return \App\Model\Gtm\Data\DataLayerProduct[]
     */
    public function createDataLayerProductsFromProducts(array $products, string $locale): array
    {
        $productsDataIndexedByProductId = $this->getElasticProductsDataIndexedById(array_map(fn (Product $product) => $product->getId(), $products));
        $dataLayerProducts = [];
        foreach ($products as $product) {
            $categoryPath = $productsDataIndexedByProductId[$product->getId()]['main_category_path'] ?? '';
            $dataLayerProduct = new DataLayerProduct();
            $this->mapProductToDataLayerProduct($product, $dataLayerProduct, $locale, false, null, $categoryPath);
            $dataLayerProducts[] = $dataLayerProduct;
        }

        return $dataLayerProducts;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param \App\Model\Gtm\Data\DataLayerProduct $dataLayerProduct
     * @param string $locale
     * @param bool $isGift
     * @param bool $isSale
     * @param string $categoryPath
     */
    public function mapProductToDataLayerProduct(
        Product $product,
        DataLayerProduct $dataLayerProduct,
        string $locale,
        bool $isGift = false,
        ?bool $isSale = null,
        string $categoryPath = ''
    ): void {
        $dataLayerProduct->setName((string)$product->getName($locale));
        $dataLayerProduct->setId((string)$product->getId());
        $dataLayerProduct->setSku((string)$product->getEan());
        $dataLayerProduct->setCatNumber((string)$product->getCatnum());

        if ($isGift) {
            $dataLayerProduct->setPrice('0.0');
            $dataLayerProduct->setTax('0.0');
            $dataLayerProduct->setPriceWithTax('0.0');
        } else {
            $sellingPrice = $this->productCachedAttributesFacade->getProductSellingPrice($product, $isSale);

            if ($sellingPrice !== null) {
                $dataLayerProduct->setPrice($sellingPrice->getPriceWithoutVat()->getAmount());
                $dataLayerProduct->setTax($sellingPrice->getVatAmount()->getAmount());
                $dataLayerProduct->setPriceWithTax($sellingPrice->getPriceWithVat()->getAmount());
            }
        }

        if ($product->getBrand() !== null) {
            $dataLayerProduct->setBrand($product->getBrand()->getName());
        }

        if ($product->isVariant()) {
            $dataLayerProduct->setVariant($product->getVariantAlias($locale) ?? '');
        }

        if ($isGift) {
            $dataLayerProduct->setProductType('dárek');
        } elseif ($product->isPohodaProductTypeSet() || $product->isSupplierSet()) {
            $dataLayerProduct->setProductType('set');
        }

        $dataLayerProduct->setCategory($categoryPath);
        $dataLayerProduct->setAvailability($product->getCalculatedAvailability()->getName($locale));
        $saleFlag = $this->flagFacade->getSaleFlag();
        $dataLayerProduct->setLabels(array_map(fn (Flag $flag) => $flag->isClearance() ? $saleFlag->getName($locale) : $flag->getName($locale), $product->getActiveFlags()));
    }

    /**
     * @param \App\Model\Order\Order $order
     * @param string $locale
     * @return array
     */
    public function createDataLayerPurchaseFromOrder(Order $order, string $locale): array
    {
        $productItems = [...$order->getProductItems(), ...$order->getGiftItems()];
        $productsData = [];
        $productsDataIndexedByProductId = $this->getElasticProductsDataIndexedById(
            array_filter(array_map(fn (OrderItem $productItem) => $productItem->getProduct() !== null ? $productItem->getProduct()->getId() : null, $productItems)));
        foreach ($productItems as $productItem) {
            $product = $productItem->getProduct();

            if ($product === null) {
                continue;
            }
            $categoryPath = $productsDataIndexedByProductId[$product->getId()]['main_category_path'] ?? '';
            $productsData[] = $this->createDataLayerPurchaseProductFromOrderProductItem($productItem, $locale, $categoryPath);
        }

        $revenue = $order->getTotalPriceWithoutVat()
            ->subtract($order->getOrderTransport()->getPriceWithoutVat())
            ->subtract($order->getOrderPayment()->getPriceWithoutVat());
        $revenueWithTax = $order->getTotalPriceWithVat()
            ->subtract($order->getOrderTransport()->getPriceWithVat())
            ->subtract($order->getOrderPayment()->getPriceWithVat());
        $shipping = $order->getOrderTransport()->getPriceWithoutVat();
        $shippingWithTax = $order->getOrderTransport()->getPriceWithVat();
        $shippingTax = $order->getOrderTransport()->getPriceWithVat()->subtract($order->getOrderTransport()->getPriceWithoutVat());

        $payment = $order->getOrderPayment()->getPriceWithoutVat();
        $paymentWithTax = $order->getOrderPayment()->getPriceWithVat();
        $paymentTax = $order->getOrderPayment()->getPriceWithVat()->subtract($order->getOrderPayment()->getPriceWithoutVat());
        $tax = $order->getTotalVatAmount()->subtract($shippingTax)->subtract($paymentTax);

        $orderDomainConfig = $this->domain->getDomainConfigById($order->getDomainId());
        $affiliation = $orderDomainConfig->getName();
        $priceBeforeDiscounts = array_sum(array_map(fn (OrderItem $orderItem) => (float)$orderItem->getTotalPriceWithVat()->getAmount(), $order->getProductItems()));

        $dataLayerPurchase = [
            'actionField' => [
                'id' => $order->getNumber(),
                'affiliation' => $affiliation,
                'revenue' => $this->getMoneyAsString($revenue),
                'revenueWithTax' => $this->getMoneyAsString($revenueWithTax),
                'tax' => $this->getMoneyAsString($tax),
                'shipping' => $this->getMoneyAsString($shipping->add($payment)),
                'shippingWithTax' => $this->getMoneyAsString($shippingWithTax->add($paymentWithTax)),
                'shippingTax' => $this->getMoneyAsString($shippingTax->add($paymentTax)),
                'priceBeforeDiscounts' => $priceBeforeDiscounts,
            ],
            'products' => $productsData,
        ];

        $gtmCoupons = $order->getGtmCoupons();
        $couponsArray = [];

        if ($gtmCoupons !== null) {
            foreach (explode(Order::PROMO_CODES_SEPARATOR, $gtmCoupons) as $key => $couponData) {
                $couponsArray['coupon'] = $couponData . (string)$priceBeforeDiscounts;
            }
        }

        foreach ($order->getItems() as $item) {
            if ($item->isTypeOrderDiscount()) {
                $couponsArray['coupon'] = $item->getName() . '|' . (string)$priceBeforeDiscounts;
            }
        }

        $dataLayerPurchase['actionField'] = array_merge($dataLayerPurchase['actionField'], $couponsArray);

        return $dataLayerPurchase;
    }

    /**
     * @param \App\Model\Order\Item\OrderItem $productItem
     * @param string $locale
     * @param string $categoryPath
     * @return array
     */
    private function createDataLayerPurchaseProductFromOrderProductItem(OrderItem $productItem, string $locale, string $categoryPath): array
    {
        $product = $productItem->getProduct();
        $price = $productItem->getPriceWithoutVat();
        $tax = $productItem->getPriceWithVat()->subtract($productItem->getPriceWithoutVat());
        $priceWithTax = $productItem->getPriceWithVat();

        $orderProductData = [
            'name' => $product->getName($locale),
            'id' => $product->getId(),
            'sku' => $product->getCatnum(),
            'catNumber' => $product->getCatnum(),
            'price' => $this->getMoneyAsString($price),
            'tax' => $this->getMoneyAsString($tax),
            'priceWithTax' => $this->getMoneyAsString($priceWithTax),
            'brand' => ($product->getBrand() === null) ? '' : $product->getBrand()->getName(),
            'category' => $categoryPath,
            'availability' => $this->gtmHelper->getGtmAvailabilityByOrderItem($productItem),
            'quantity' => $productItem->getQuantity(),
        ];

        if ($product->isVariant()) {
            $orderProductData['variant'] = $product->getVariantAlias($locale) ?? '';
        }

        if ($productItem->isTypeGift()) {
            $orderProductData['product_type'] = 'dárek';
        } elseif ($product->isPohodaProductTypeSet() || $product->isSupplierSet()) {
            $orderProductData['product_type'] = 'set';
        }

        return $orderProductData;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Money\Money $price
     * @return string
     */
    private function getMoneyAsString(Money $price): string
    {
        return $price->round(self::PRICE_SCALE)->getAmount();
    }

    /**
     * @param int[] $productIds
     * @return array
     */
    private function getElasticProductsDataIndexedById(array $productIds): array
    {
        return $this->productOnCurrentDomainElasticFacade->getSellableHitsForIds(array_combine($productIds, $productIds));
    }
}
