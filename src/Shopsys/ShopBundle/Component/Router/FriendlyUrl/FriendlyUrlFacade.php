<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Router\FriendlyUrl;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Router\DomainRouterFactory;
use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrl;
use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlFacade as BaseFriendlyUrlFacade;
use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlFactoryInterface;
use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlRepository;
use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlUniqueResultFactory;
use Shopsys\ShopBundle\Component\Router\FriendlyUrl\Exception\FriendlyUrlExistsException;

class FriendlyUrlFacade extends BaseFriendlyUrlFacade
{
    public const MAX_URL_UNIQUE_RESOLVE_ATTEMPT = 10000;

    /**
     * @var \Shopsys\ShopBundle\Component\Router\FriendlyUrl\FriendlyUrlCacheFacade
     */
    private $friendlyUrlCacheFacade;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\FrameworkBundle\Component\Router\DomainRouterFactory $domainRouterFactory
     * @param \Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlUniqueResultFactory $friendlyUrlUniqueResultFactory
     * @param \Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlRepository $friendlyUrlRepository
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlFactoryInterface $friendlyUrlFactory
     * @param \Shopsys\ShopBundle\Component\Router\FriendlyUrl\FriendlyUrlCacheFacade $friendlyUrlCacheFacade
     */
    public function __construct(
        EntityManagerInterface $em,
        DomainRouterFactory $domainRouterFactory,
        FriendlyUrlUniqueResultFactory $friendlyUrlUniqueResultFactory,
        FriendlyUrlRepository $friendlyUrlRepository,
        Domain $domain,
        FriendlyUrlFactoryInterface $friendlyUrlFactory,
        FriendlyUrlCacheFacade $friendlyUrlCacheFacade
    ) {
        parent::__construct($em, $domainRouterFactory, $friendlyUrlUniqueResultFactory, $friendlyUrlRepository, $domain, $friendlyUrlFactory);
        $this->friendlyUrlCacheFacade = $friendlyUrlCacheFacade;
    }

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

    /**
     * @param int $domainId
     * @param string $routeName
     * @param int $entityId
     * @return \Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrl|null
     */
    public function findMainFriendlyUrl($domainId, $routeName, $entityId)
    {
        return $this->friendlyUrlRepository->findMainFriendlyUrl($domainId, $routeName, $entityId);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrl $mainFriendlyUrl
     */
    protected function setFriendlyUrlAsMain(FriendlyUrl $mainFriendlyUrl)
    {
        $friendlyUrls = $this->friendlyUrlRepository->getAllByRouteNameAndEntityIdAndDomainId(
            $mainFriendlyUrl->getRouteName(),
            $mainFriendlyUrl->getEntityId(),
            $mainFriendlyUrl->getDomainId()
        );
        foreach ($friendlyUrls as $friendlyUrl) {
            $friendlyUrl->setMain(false);
        }
        $mainFriendlyUrl->setMain(true);

        $this->em->flush($friendlyUrls);

        $this->friendlyUrlCacheFacade->saveToCache($mainFriendlyUrl);
    }
}
