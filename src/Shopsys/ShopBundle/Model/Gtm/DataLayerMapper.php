<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Gtm;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Order\Preview\OrderPreview;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\FrameworkBundle\Model\Security\Roles;
use Shopsys\ShopBundle\Model\Category\Category;
use Shopsys\ShopBundle\Model\Category\CategoryFacade;
use Shopsys\ShopBundle\Model\Customer\User as Customer;
use Shopsys\ShopBundle\Model\Gtm\Data\DataLayerPage;
use Shopsys\ShopBundle\Model\Gtm\Data\DataLayerProduct;
use Shopsys\ShopBundle\Model\Gtm\Data\DataLayerUser;
use Shopsys\ShopBundle\Model\Order\Item\OrderItem;
use Shopsys\ShopBundle\Model\Order\Order;
use Shopsys\ShopBundle\Model\Product\ProductCachedAttributesFacade;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

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
        'front_store_index' => DataLayerPage::TYPE_STORES,
        'front_about_us_info' => DataLayerPage::TYPE_ABOUT_US,
    ];

    /**
     * @var \Shopsys\ShopBundle\Model\Category\CategoryFacade
     */
    private $categoryFacade;

    /**
     * @var \Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductCachedAttributesFacade
     */
    private $productCachedAttributesFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Gtm\GtmHelper
     */
    private $gtmHelper;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * DataLayerMapper constructor.
     * @param \Shopsys\ShopBundle\Model\Category\CategoryFacade $categoryFacade
     * @param \Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface $authorizationChecker
     * @param \Shopsys\ShopBundle\Model\Product\ProductCachedAttributesFacade $productCachedAttributesFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\ShopBundle\Model\Gtm\GtmHelper $gtmHelper
     */
    public function __construct(
        CategoryFacade $categoryFacade,
        AuthorizationCheckerInterface $authorizationChecker,
        ProductCachedAttributesFacade $productCachedAttributesFacade,
        Domain $domain,
        GtmHelper $gtmHelper
    ) {
        $this->categoryFacade = $categoryFacade;
        $this->authorizationChecker = $authorizationChecker;
        $this->productCachedAttributesFacade = $productCachedAttributesFacade;
        $this->domain = $domain;
        $this->gtmHelper = $gtmHelper;
    }

    /**
     * @param $routeName
     * @param \Shopsys\ShopBundle\Model\Gtm\Data\DataLayerPage $dataLayerPage
     */
    public function mapRouteNameToDataLayerPage($routeName, DataLayerPage $dataLayerPage): void
    {
        $dataLayerPage->setType(
            self::ROUTE_NAMES_TO_PAGE_TYPE[$routeName] ?? DataLayerPage::TYPE_OTHER
        );
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Customer\User|null $currentCustomer
     * @param \Shopsys\ShopBundle\Model\Gtm\Data\DataLayerUser $dataLayerUser
     */
    public function mapCurrentCustomerToDataLayerUser(?Customer $currentCustomer, DataLayerUser $dataLayerUser): void
    {
        if ($currentCustomer !== null) {
            $dataLayerUser->setId((string)$currentCustomer->getId());
            $dataLayerUser->setState(DataLayerUser::STATE_LOGGED_IN);

            if ($this->authorizationChecker->isGranted(Roles::ROLE_ADMIN_AS_CUSTOMER)) {
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
     * @param \Shopsys\ShopBundle\Model\Category\Category $category
     * @param \Shopsys\ShopBundle\Model\Gtm\Data\DataLayerPage $dataLayerPage
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
     * @param \Shopsys\ShopBundle\Model\Category\Category $category
     * @param \Shopsys\ShopBundle\Model\Gtm\Data\DataLayerPage $dataLayerPage
     * @param string $locale
     */
    private function mapCategoryToDataLayerPageCategory(Category $category, DataLayerPage $dataLayerPage, string $locale): void
    {
        $categoriesInPath = $this->categoryFacade->getCategoriesInPath($category);
        $categoriesIdsInPath = [];
        $categoriesNamesInPath = [];
        foreach ($categoriesInPath as $categoryInPath) {
            /** @var $categoryInPath \Shopsys\ShopBundle\Model\Category\Category */
            $categoriesIdsInPath[] = (string)$categoryInPath->getId();
            $categoriesNamesInPath[] = $categoryInPath->getName($locale);
        }

        $dataLayerPage->setCategoryId($categoriesIdsInPath);
        $dataLayerPage->setCategory($categoriesNamesInPath);
        $dataLayerPage->setCategoryLevel((string)$category->getLevel());
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $product
     * @param \Shopsys\ShopBundle\Model\Gtm\Data\DataLayerPage $dataLayerPage
     * @param string $locale
     */
    public function mapProductToDataLayerPage(Product $product, DataLayerPage $dataLayerPage, string $locale): void
    {
        /** @var \Shopsys\ShopBundle\Model\Category\Category $productMainCategory */
        $productMainCategory = $this->categoryFacade->getProductMainCategoryByDomainId($product, Domain::MAIN_ADMIN_DOMAIN_ID);

        $this->mapCategoryToDataLayerPageCategory($productMainCategory, $dataLayerPage, $locale);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Order\Preview\OrderPreview $orderPreview
     * @param string $locale
     * @return \Shopsys\ShopBundle\Model\Gtm\Data\DataLayerProduct[]
     */
    public function createDataLayerProductsFromOrderPreview(OrderPreview $orderPreview, string $locale): array
    {
        $quantifiedProducts = $orderPreview->getQuantifiedProducts();

        $dataLayerProducts = [];
        foreach ($quantifiedProducts as $index => $quantifiedProduct) {
            $product = $quantifiedProduct->getProduct();
            $quantity = $quantifiedProduct->getQuantity();

            if ($product === null) {
                continue;
            }

            $dataLayerProduct = new DataLayerProduct();
            $this->mapProductToDataLayerProduct($product, $dataLayerProduct, $locale);
            $dataLayerProduct->setQuantity($quantity);
            $dataLayerProducts[] = $dataLayerProduct;
        }

        return $dataLayerProducts;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Product[] $products
     * @param string $locale
     * @return \Shopsys\ShopBundle\Model\Gtm\Data\DataLayerProduct[]
     */
    public function createDataLayerProductsFromProducts(array $products, string $locale): array
    {
        $dataLayerProducts = [];
        foreach ($products as $product) {
            $dataLayerProduct = new DataLayerProduct();
            $this->mapProductToDataLayerProduct($product, $dataLayerProduct, $locale);
            $dataLayerProducts[] = $dataLayerProduct;
        }

        return $dataLayerProducts;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $product
     * @param \Shopsys\ShopBundle\Model\Gtm\Data\DataLayerProduct $dataLayerProduct
     * @param string $locale
     */
    public function mapProductToDataLayerProduct(Product $product, DataLayerProduct $dataLayerProduct, string $locale): void
    {
        $dataLayerProduct->setName((string)$product->getName($locale));
        $dataLayerProduct->setId((string)$product->getId());
        $dataLayerProduct->setSku((string)$product->getEan());
        $dataLayerProduct->setCatNumber((string)$product->getCatnum());

        $sellingPrice = $this->productCachedAttributesFacade->getProductSellingPrice($product);

        if ($sellingPrice !== null) {
            $dataLayerProduct->setPrice($sellingPrice->getPriceWithoutVat()->getAmount());
            $dataLayerProduct->setTax($sellingPrice->getVatAmount()->getAmount());
            $dataLayerProduct->setPriceWithTax($sellingPrice->getPriceWithVat()->getAmount());
        }

        $dataLayerProduct->setVariant($this->gtmHelper->getVariantByProduct($product));

        if ($product->getBrand() !== null) {
            $dataLayerProduct->setBrand($product->getBrand()->getName());
        }

        /** @var \Shopsys\ShopBundle\Model\Category\Category $productMainCategory */
        $productMainCategory = $this->categoryFacade->getProductMainCategoryByDomainId($product, Domain::MAIN_ADMIN_DOMAIN_ID);
        $dataLayerProduct->setCategory($this->categoryFacade->getCategoriesNamesInPathAsString($productMainCategory, $locale));
        $dataLayerProduct->setAvailability($product->getCalculatedAvailability()->getName($locale));

        $dataLayerProduct->setLabels(array_map(function ($flag) use ($locale) {
            /** @var $flag \Shopsys\ShopBundle\Model\Product\Flag\Flag */
            return $flag->getName($locale);
        }, $product->getFlags()->toArray()));
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     * @param string $locale
     * @return array
     */
    public function createDataLayerPurchaseFromOrder(Order $order, string $locale): array
    {
        $productItems = $order->getProductItems();
        $productsData = [];
        foreach ($productItems as $productItem) {
            $product = $productItem->getProduct();

            if ($product === null) {
                continue;
            }

            $productsData[] = $this->createDataLayerPurchaseProductFromOrderProductItem($productItem, $locale);
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

        $paymentTax = $order->getOrderPayment()->getPriceWithVat()->subtract($order->getOrderPayment()->getPriceWithoutVat());
        $tax = $order->getTotalVatAmount()->subtract($shippingTax)->subtract($paymentTax);

        $orderDomainConfig = $this->domain->getDomainConfigById($order->getDomainId());
        $affiliation = $orderDomainConfig->getName();

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
            ],
            'products' => $productsData,
        ];

        $gtmCoupons = $order->getGtmCoupons();
        if ($gtmCoupons !== null) {
            $couponsArray = [];
            foreach (explode(Order::PROMO_CODES_SEPARATOR, $gtmCoupons) as $key => $couponData) {
                $couponNumber = $key + 1;
                $couponsArray['coupon' . $couponNumber] = $couponData;
            }
            $dataLayerPurchase['actionField'] = array_merge($dataLayerPurchase['actionField'], $couponsArray);
        }

        return $dataLayerPurchase;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Item\OrderItem $productItem
     * @param string $locale
     * @return array
     */
    private function createDataLayerPurchaseProductFromOrderProductItem(OrderItem $productItem, string $locale): array
    {
        $product = $productItem->getProduct();
        $price = $productItem->getPriceWithoutVat();
        $tax = $productItem->getPriceWithVat()->subtract($productItem->getPriceWithoutVat());
        $priceWithTax = $productItem->getPriceWithVat();

        /** @var \Shopsys\ShopBundle\Model\Category\Category $productMainCategory */
        $productMainCategory = $this->categoryFacade->getProductMainCategoryByDomainId($product, Domain::MAIN_ADMIN_DOMAIN_ID);

        $productData = [
            'name' => $product->getName($locale),
            'id' => $product->getId(),
            'sku' => $product->getCatnum(),
            'catNumber' => $product->getCatnum(),
            'price' => $this->getMoneyAsString($price),
            'tax' => $this->getMoneyAsString($tax),
            'priceWithTax' => $this->getMoneyAsString($priceWithTax),
            'brand' => ($product->getBrand() === null) ? '' : $product->getBrand()->getName(),
            'category' => $this->categoryFacade->getCategoriesNamesInPathAsString($productMainCategory, $locale),
            'variant' => $this->gtmHelper->getVariantByProduct($product),
            'availability' => $this->gtmHelper->getGtmAvailabilityByOrderItem($productItem),
            'quantity' => $productItem->getQuantity(),
        ];

        return $productData;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Money\Money $price
     * @return string
     */
    private function getMoneyAsString(Money $price): string
    {
        return $price->round(self::PRICE_SCALE)->getAmount();
    }
}
