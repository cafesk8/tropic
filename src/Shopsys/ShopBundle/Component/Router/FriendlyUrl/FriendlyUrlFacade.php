<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Router\FriendlyUrl;

use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlFacade as BaseFriendlyUrlFacade;
use Shopsys\ShopBundle\Component\Router\FriendlyUrl\Exception\FriendlyUrlExistsException;

class FriendlyUrlFacade extends BaseFriendlyUrlFacade
{
    public const MAX_URL_UNIQUE_RESOLVE_ATTEMPT = 10000;

    /**
     * @param string $routeName
     * @param int $entityId
     * @param int $domainId
     * @param string $slug
     */
    public function addNotMainFriendlyUrl(string $routeName, int $entityId, int $domainId, string $slug): void
    {
        $friendlyUrl = $this->friendlyUrlRepository->findByDomainIdAndSlug($domainId, $slug);

        if ($friendlyUrl !== null) {
            throw new FriendlyUrlExistsException();
        }

        $newFriendlyUrl = $this->friendlyUrlFactory->create($routeName, $entityId, $domainId, $slug);
        $newFriendlyUrl->setMain(false);

        $this->em->persist($newFriendlyUrl);
        $this->em->flush($newFriendlyUrl);
    }
}
