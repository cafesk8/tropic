<?php

declare(strict_types=1);

namespace App\Component\Router\FriendlyUrl;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\Exception\FriendlyUrlNotFoundException;
use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrl;
use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlRepository as BaseFriendlyUrlRepository;

class FriendlyUrlRepository extends BaseFriendlyUrlRepository
{
    private FriendlyUrlCacheFacade $friendlyUrlCacheFacade;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \App\Component\Router\FriendlyUrl\FriendlyUrlCacheFacade $friendlyUrlCacheFacade
     */
    public function __construct(EntityManagerInterface $em, FriendlyUrlCacheFacade $friendlyUrlCacheFacade)
    {
        parent::__construct($em);
        $this->friendlyUrlCacheFacade = $friendlyUrlCacheFacade;
    }

    /**
     * @param int $domainId
     * @param string $routeName
     * @param int $entityId
     * @return \Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrl
     */
    public function getMainFriendlyUrl($domainId, $routeName, $entityId)
    {
        $friendlyUrl = $this->friendlyUrlCacheFacade->findFromCache($routeName, $domainId, $entityId);

        if ($friendlyUrl === null) {
            $criteria = [
                'domainId' => $domainId,
                'routeName' => $routeName,
                'entityId' => $entityId,
                'main' => true,
            ];
            $friendlyUrl = $this->getFriendlyUrlRepository()->findOneBy($criteria);

            if ($friendlyUrl !== null) {
                $this->friendlyUrlCacheFacade->saveToCache($friendlyUrl);
            }
        }

        if ($friendlyUrl === null) {
            throw new FriendlyUrlNotFoundException();
        }

        return $friendlyUrl;
    }

    /**
     * @param string $slug
     * @param int $domainId
     * @return \Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrl
     */
    public function getFriendlyUrlBySlugAndDomainId(string $slug, int $domainId): FriendlyUrl
    {
        $criteria = [
            'slug' => $slug,
            'domainId' => $domainId,
        ];
        /** @var \Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrl|null $friendlyUrl */
        $friendlyUrl = $this->getFriendlyUrlRepository()->findOneBy($criteria);

        if ($friendlyUrl === null) {
            throw new FriendlyUrlNotFoundException();
        }

        return $friendlyUrl;
    }
}
