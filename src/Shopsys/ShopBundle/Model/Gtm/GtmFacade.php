<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Gtm;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Customer\CurrentCustomer;
use Shopsys\FrameworkBundle\Model\Order\Preview\OrderPreview;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFacade;
use Shopsys\ShopBundle\Model\Category\Category;
use Shopsys\ShopBundle\Model\Gtm\Data\DataLayerPage;
use Shopsys\ShopBundle\Model\Gtm\Data\DataLayerUser;
use Shopsys\ShopBundle\Model\Order\Order;
use Shopsys\ShopBundle\Model\Product\Product;

class GtmFacade
{
    /**
     * @var \Shopsys\ShopBundle\Model\Gtm\GtmContainer
     */
    private $gtmContainer;

    /**
     * @var \Shopsys\ShopBundle\Model\Gtm\DataLayer
     */
    private $dataLayer;

    /**
     * @var \Shopsys\ShopBundle\Model\Gtm\DataLayerMapper
     */
    private $dataLayerMapper;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Customer\CurrentCustomer
     */
    private $currentCustomer;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFacade
     */
    private $currencyFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * GtmFacade constructor.
     * @param \Shopsys\ShopBundle\Model\Gtm\GtmContainer $gtmContainer
     * @param \Shopsys\ShopBundle\Model\Gtm\DataLayerMapper $dataLayerMapper
     * @param \Shopsys\FrameworkBundle\Model\Customer\CurrentCustomer $currentCustomer
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFacade $currencyFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(
        GtmContainer $gtmContainer,
        DataLayerMapper $dataLayerMapper,
        CurrentCustomer $currentCustomer,
        CurrencyFacade $currencyFacade,
        Domain $domain
    ) {
        $this->gtmContainer = $gtmContainer;
        $this->dataLayerMapper = $dataLayerMapper;
        $this->currentCustomer = $currentCustomer;
        $this->currencyFacade = $currencyFacade;
        $this->domain = $domain;

        $this->dataLayer = $this->gtmContainer->getDataLayer();
    }

    /**
     * @param string $routeName
     */
    public function onAllFrontPages(string $routeName): void
    {
        if (!$this->gtmContainer->isEnabled()) {
            return;
        }

        $dataLayerPage = new DataLayerPage();
        $this->dataLayer->set('page', $dataLayerPage);
        $this->dataLayerMapper->mapRouteNameToDataLayerPage($routeName, $dataLayerPage);

        $dataLayerUser = new DataLayerUser();
        $this->dataLayer->set('user', $dataLayerUser);

        $currentCustomer = $this->currentCustomer->findCurrentUser();
        $this->dataLayerMapper->mapCurrentCustomerToDataLayerUser($currentCustomer, $dataLayerUser);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Category\Category $category
     */
    public function onProductListByCategoryPage(Category $category): void
    {
        if (!$this->gtmContainer->isEnabled()) {
            return;
        }

        $dataLayerPage = $this->dataLayer->get('page');
        $this->dataLayerMapper->mapCategoryToDataLayerPage($category, $dataLayerPage, $this->dataLayer->getLocale());
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     */
    public function onProductDetailPage(Product $product): void
    {
        if (!$this->gtmContainer->isEnabled()) {
            return;
        }

        $dataLayerPage = $this->dataLayer->get('page');
        $this->dataLayerMapper->mapProductToDataLayerPage($product, $dataLayerPage, $this->dataLayer->getLocale());

        $gtmEventData = [
            'ecommerce' => [
                'currencyCode' => $this->getCurrentDomainDefaultCurrencyCode(),
                'detail' => [
                    'products' => $this->dataLayerMapper->createDataLayerProductsFromProducts([$product], $this->dataLayer->getLocale()),
                ],
            ],
        ];

        $this->dataLayer->addEvent('ec.productDetail', $gtmEventData);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Order\Preview\OrderPreview $orderPreview
     */
    public function onCartPage(OrderPreview $orderPreview): void
    {
        if (!$this->gtmContainer->isEnabled()) {
            return;
        }

        $gtmEventData = [
            'ecommerce' => [
                'currencyCode' => $this->getCurrentDomainDefaultCurrencyCode(),
                'checkout' => [
                    'actionField' => [
                        'step' => 1,
                    ],
                    'products' => $this->dataLayerMapper->createDataLayerProductsFromOrderPreview($orderPreview, $this->dataLayer->getLocale()),
                ],
            ],
        ];

        $this->dataLayer->addEvent('ec.checkout', $gtmEventData);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Order\Preview\OrderPreview $orderPreview
     */
    public function onOrderTransportAndPaymentPage(OrderPreview $orderPreview): void
    {
        if (!$this->gtmContainer->isEnabled()) {
            return;
        }

        $this->dataLayer->get('page')->setType(DataLayerPage::TYPE_ORDER_STEP2);

        $gtmEventData = [
            'ecommerce' => [
                'currencyCode' => $this->getCurrentDomainDefaultCurrencyCode(),
                'checkout' => [
                    'actionField' => [
                        'step' => 2,
                    ],
                    'products' => $this->dataLayerMapper->createDataLayerProductsFromOrderPreview($orderPreview, $this->dataLayer->getLocale()),
                ],
            ],
        ];

        $this->dataLayer->addEvent('ec.checkout', $gtmEventData);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Order\Preview\OrderPreview $orderPreview
     */
    public function onOrderDeliveryPage(OrderPreview $orderPreview): void
    {
        if (!$this->gtmContainer->isEnabled()) {
            return;
        }

        $this->dataLayer->get('page')->setType(DataLayerPage::TYPE_ORDER_STEP3);

        $locale = $this->dataLayer->getLocale();

        $transportName = $orderPreview->getTransport()->getName($locale);
        $paymentName = $orderPreview->getPayment()->getName($locale);

        $gtmCheckoutOptionEventData = [
            'ecommerce' => [
                'checkout_option' => [
                    'actionField' => [
                        'step' => 2,
                        'option' => $transportName . '|' . $paymentName,
                    ],
                ],
            ],
        ];

        $this->dataLayer->addEvent('ec.checkout_option', $gtmCheckoutOptionEventData);

        $gtmCheckoutEventData = [
            'ecommerce' => [
                'currencyCode' => $this->getCurrentDomainDefaultCurrencyCode(),
                'checkout' => [
                    'actionField' => [
                        'step' => 3,
                    ],
                    'products' => $this->dataLayerMapper->createDataLayerProductsFromOrderPreview($orderPreview, $locale),
                ],
            ],
        ];

        $this->dataLayer->addEvent('ec.checkout', $gtmCheckoutEventData);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     */
    public function onOrderSentPage(Order $order): void
    {
        if (!$this->gtmContainer->isEnabled()) {
            return;
        }

        $locale = $this->dataLayer->getLocale();

        $gtmPurchaseEventData = [
            'ecommerce' => [
                'currencyCode' => $order->getCurrency()->getCode(),
                'purchase' => $this->dataLayerMapper->createDataLayerPurchaseFromOrder($order, $locale),
            ],
        ];

        $this->dataLayer->addEvent('ec.purchase', $gtmPurchaseEventData);
    }

    /**
     * @return string
     */
    private function getCurrentDomainDefaultCurrencyCode(): string
    {
        $currency = $this->currencyFacade->getDomainDefaultCurrencyByDomainId($this->domain->getId());

        return $currency->getCode();
    }
}
