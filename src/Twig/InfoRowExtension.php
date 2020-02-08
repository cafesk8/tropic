<?php

declare(strict_types=1);

namespace App\Twig;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use App\Component\InfoRow\InfoRowFacade;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class InfoRowExtension extends AbstractExtension
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \App\Component\InfoRow\InfoRowFacade
     */
    private $infoRowFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Component\InfoRow\InfoRowFacade $infoRowFacade
     */
    public function __construct(
        Domain $domain,
        InfoRowFacade $infoRowFacade
    ) {
        $this->domain = $domain;
        $this->infoRowFacade = $infoRowFacade;
    }

    /**
     * @return array
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('isInfoRowVisible', [$this, 'isInfoRowVisible']),
            new TwigFunction('getInfoRowText', [$this, 'getInfoRowText'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * @return bool
     */
    public function isInfoRowVisible(): bool
    {
        $currentDomainId = $this->domain->getId();

        return $this->infoRowFacade->isRowVisibleForCurrentCustomerUser();
    }

    /**
     * @return string|null
     */
    public function getInfoRowText(): ?string
    {
        $currentDomainId = $this->domain->getId();

        return $this->infoRowFacade->getRowText($currentDomainId);
    }
}
