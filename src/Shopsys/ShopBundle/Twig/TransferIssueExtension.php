<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Twig;

use Shopsys\ShopBundle\Model\Transfer\Issue\TransferIssueFacade;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TransferIssueExtension extends AbstractExtension
{
    /**
     * @var \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var \Shopsys\ShopBundle\Model\Transfer\Issue\TransferIssueFacade
     */
    protected $transferIssueFacade;

    /**
     * @param \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface $tokenStorage
     * @param \Shopsys\ShopBundle\Model\Transfer\Issue\TransferIssueFacade $transferIssueFacade
     */
    public function __construct(TokenStorageInterface $tokenStorage, TransferIssueFacade $transferIssueFacade)
    {
        $this->tokenStorage = $tokenStorage;
        $this->transferIssueFacade = $transferIssueFacade;
    }

    /**
     * @return \Twig\TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('getUnseenTransferIssuesCount', [$this, 'getUnseenTransferIssuesCount']),
        ];
    }

    /**
     * @return int
     */
    public function getUnseenTransferIssuesCount(): int
    {
        $token = $this->tokenStorage->getToken();
        /** @var \Shopsys\ShopBundle\Model\Administrator\Administrator $administrator */
        $administrator = $token->getUser();

        return $this->transferIssueFacade->getUnseenTransferIssuesCount($administrator);
    }
}
