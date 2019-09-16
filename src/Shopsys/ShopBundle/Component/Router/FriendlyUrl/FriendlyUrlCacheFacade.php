<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Router\FriendlyUrl;

use Doctrine\Common\Cache\CacheProvider;
use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrl;

class FriendlyUrlCacheFacade
{
    /**
     * @var \Doctrine\Common\Cache\CacheProvider
     */
    private $cacheProvider;

    /**
     * @param \Doctrine\Common\Cache\CacheProvider $cacheProvider
     */
    public function __construct(CacheProvider $cacheProvider)
    {
        $this->cacheProvider = $cacheProvider;
    }

    /**
     * @param string $routeName
     * @param int $domainId
     * @param int $entityId
     * @return \Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrl|null
     */
    public function findFromCache(string $routeName, int $domainId, int $entityId): ?FriendlyUrl
    {
        $cacheId = $this->getCacheId($routeName, $domainId, $entityId);
        $friendlyUrl = $this->cacheProvider->fetch($cacheId);

        return $friendlyUrl === false ? null : $friendlyUrl;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @param string $locale
     * @param \Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrl $friendlyUrl
     * @param \Shopsys\ShopBundle\Model\Product\ProductDistinguishingParameterValue $productDistinguishingParameterValue
     */
    public function saveToCache(FriendlyUrl $friendlyUrl): void
    {
        $cacheId = $this->getCacheId($friendlyUrl->getRouteName(), $friendlyUrl->getDomainId(), $friendlyUrl->getEntityId());
        $this->cacheProvider->save($cacheId, $friendlyUrl);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @param string $routeName
     * @param int $domainId
     * @param int $entityId
     * @param string $locale
     * @return string
     */
    protected function getCacheId(string $routeName, int $domainId, int $entityId): string
    {
        return 'friendlyUrl_' . $routeName . '_' . $domainId . '_' . $entityId;
    }
}
