<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\LastVisitedProducts;

use DateTime;
use Shopsys\ShopBundle\Controller\Front\LastVisitedProductsController;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ProductDetailVisitListener implements EventSubscriberInterface
{
    public const LAST_VISITED_PRODUCTS_COOKIE = 'lastVisitedProducts';
    public const COOKIE_EXPIRE_IN_YEARS = 1;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    /**
     * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        if ($event->getRequest()->attributes->get('_route') === 'front_product_detail') {
            $productIds = $this->getLastVisitedProductIdsWithCurrentOne($event);

            $event->getResponse()->headers->setCookie($this->createLastVisitedProductIdsCookie($productIds));
        }
    }

    /**
     * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
     * @return int[]
     */
    private function getLastVisitedProductIdsWithCurrentOne(FilterResponseEvent $event): array
    {
        $productId = $event->getRequest()->attributes->get('id');
        $cookieProductIds = $event->getRequest()->cookies->get(self::LAST_VISITED_PRODUCTS_COOKIE, '');

        $productIds = explode(LastVisitedProductsFacade::COOKIE_PRODUCT_IDS_DELIMITER, $cookieProductIds);
        array_unshift($productIds, $productId);
        $productIds = array_map('intval', $productIds);
        $productIds = array_unique($productIds);
        $productIds = array_slice($productIds, 0, LastVisitedProductsController::MAX_VISITED_PRODUCT_COUNT);

        return $productIds;
    }

    /**
     * @param int[] $productIds
     * @return \Symfony\Component\HttpFoundation\Cookie
     */
    private function createLastVisitedProductIdsCookie($productIds): Cookie
    {
        return new Cookie(
            self::LAST_VISITED_PRODUCTS_COOKIE,
            implode(LastVisitedProductsFacade::COOKIE_PRODUCT_IDS_DELIMITER, $productIds),
            new DateTime('+' . self::COOKIE_EXPIRE_IN_YEARS . ' years'),
            '/',
            null,
            false,
            false
        );
    }
}
