<?php

declare(strict_types=1);

namespace App\Model\Product;

use Doctrine\Common\Cache\CacheProvider;
use App\Component\Redis\RedisFacade;

class CachedProductDistinguishingParameterValueFacade
{
    private const LIFETIME = 14400; // 4h

    /**
     * @var \Doctrine\Common\Cache\CacheProvider
     */
    private $cacheProvider;

    /**
     * @var \App\Component\Redis\RedisFacade
     */
    private $redisFacade;

    /**
     * @param \Doctrine\Common\Cache\CacheProvider $cacheProvider
     * @param \App\Component\Redis\RedisFacade $redisFacade
     */
    public function __construct(CacheProvider $cacheProvider, RedisFacade $redisFacade)
    {
        $this->cacheProvider = $cacheProvider;
        $this->redisFacade = $redisFacade;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param string $locale
     * @return \App\Model\Product\ProductDistinguishingParameterValue|null
     */
    public function findProductDistinguishingParameterValue(Product $product, string $locale): ?ProductDistinguishingParameterValue
    {
        $cacheId = $this->getCacheId($product, $locale);
        $productDistinguishingParameterValue = $this->cacheProvider->fetch($cacheId);

        return $productDistinguishingParameterValue === false ? null : $productDistinguishingParameterValue;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param string $locale
     * @param \App\Model\Product\ProductDistinguishingParameterValue $productDistinguishingParameterValue
     */
    public function saveToCache(Product $product, string $locale, ProductDistinguishingParameterValue $productDistinguishingParameterValue): void
    {
        $cacheId = $this->getCacheId($product, $locale);
        $this->cacheProvider->save($cacheId, $productDistinguishingParameterValue, static::LIFETIME);
    }

    /**
     * @param \App\Model\Product\Product $product
     */
    public function invalidCacheByProduct(Product $product): void
    {
        $this->redisFacade->clearCacheByPattern('distinguishing_parameters', $product->getId());
        foreach ($product->getVariants() as $variant) {
            $this->redisFacade->clearCacheByPattern('distinguishing_parameters', $variant->getId());
        }

        if ($product->getMainVariantGroup() !== null) {
            foreach ($product->getMainVariantGroup()->getProducts() as $mainProduct) {
                $this->redisFacade->clearCacheByPattern('distinguishing_parameters', $mainProduct->getId());
            }
        }
    }

    public function invalidAll(): void
    {
        $this->redisFacade->clearCacheByPattern('distinguishing_parameters');
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param string $locale
     * @return string
     */
    protected function getCacheId(Product $product, string $locale): string
    {
        return $product->getId() . '_' . $locale;
    }
}
