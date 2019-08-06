<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product;

use Doctrine\Common\Cache\CacheProvider;
use Shopsys\ShopBundle\Component\Redis\RedisFacade;

class CachedProductDistinguishingParameterValueFacade
{
    private const LIFETIME = 14400; // 4h

    /**
     * @var \Doctrine\Common\Cache\CacheProvider
     */
    private $cacheProvider;

    /**
     * @var \Shopsys\ShopBundle\Component\Redis\RedisFacade
     */
    private $redisFacade;

    /**
     * @param \Doctrine\Common\Cache\CacheProvider $cacheProvider
     * @param \Shopsys\ShopBundle\Component\Redis\RedisFacade $redisFacade
     */
    public function __construct(CacheProvider $cacheProvider, RedisFacade $redisFacade)
    {
        $this->cacheProvider = $cacheProvider;
        $this->redisFacade = $redisFacade;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @param string $locale
     * @return \Shopsys\ShopBundle\Model\Product\ProductDistinguishingParameterValue|null
     */
    public function findProductDistinguishingParameterValue(Product $product, string $locale): ?ProductDistinguishingParameterValue
    {
        $cacheId = $this->getCacheId($product, $locale);
        $productDistinguishingParameterValue = $this->cacheProvider->fetch($cacheId);

        return $productDistinguishingParameterValue === false ? null : $productDistinguishingParameterValue;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @param string $locale
     * @param \Shopsys\ShopBundle\Model\Product\ProductDistinguishingParameterValue $productDistinguishingParameterValue
     */
    public function saveToCache(Product $product, string $locale, ProductDistinguishingParameterValue $productDistinguishingParameterValue): void
    {
        $cacheId = $this->getCacheId($product, $locale);
        $this->cacheProvider->save($cacheId, $productDistinguishingParameterValue, static::LIFETIME);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     */
    public function invalidCacheByProduct(Product $product): void
    {
        $cacheId = $product->getId();
        $this->redisFacade->clearCacheByPattern('distinguishing_parameters', $cacheId);
    }

    public function invalidAll(): void
    {
        $this->redisFacade->clearCacheByPattern('distinguishing_parameters');
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @param string $locale
     * @return string
     */
    protected function getCacheId(Product $product, string $locale): string
    {
        return $product->getId() . '_' . $locale;
    }
}
