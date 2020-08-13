<?php

declare(strict_types=1);

namespace App\Component\Router\FriendlyUrl;

use App\Component\Domain\DomainHelper;
use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrl;
use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlFactory as BaseFriendlyUrlFactory;

class FriendlyUrlFactory extends BaseFriendlyUrlFactory
{
    private const SLUGS_BY_ROUTE_NAME_AND_DOMAIN_ID = [
        'front_sale_product_list' => [
            DomainHelper::CZECH_DOMAIN => 'vyprodej',
            DomainHelper::SLOVAK_DOMAIN => 'vypredaj',
            DomainHelper::ENGLISH_DOMAIN => 'sale',
        ],
    ];

    /**
     * @param string $routeName
     * @param int $entityId
     * @param string $entityName
     * @param int $domainId
     * @param int|null $indexPostfix
     * @return \Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrl|null
     */
    public function createIfValid(string $routeName, int $entityId, string $entityName, int $domainId, ?int $indexPostfix = null): ?FriendlyUrl
    {
        $friendlyUrl = parent::createIfValid($routeName, $entityId, $entityName, $domainId, $indexPostfix);

        if ($friendlyUrl === null || !isset(self::SLUGS_BY_ROUTE_NAME_AND_DOMAIN_ID[$routeName])) {
            return $friendlyUrl;
        }

        return $this->create(
            $routeName,
            $entityId,
            $domainId,
            self::SLUGS_BY_ROUTE_NAME_AND_DOMAIN_ID[$routeName][$domainId] . '/' . $friendlyUrl->getSlug()
        );
    }
}
