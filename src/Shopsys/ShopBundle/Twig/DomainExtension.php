<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Twig;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Domain\DomainFacade;
use Shopsys\FrameworkBundle\Twig\DomainExtension as BaseDomainExtension;
use Shopsys\ShopBundle\Component\Domain\DomainHelper;
use Shopsys\ShopBundle\Component\Domain\Exception\MissingDomainIconException;
use Shopsys\ShopBundle\Component\Domain\Exception\MissingDomainNameException;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Templating\EngineInterface;
use Twig\TwigFunction;

class DomainExtension extends BaseDomainExtension
{
    private const DOMAINS_ICON_BY_DOMAIN_ID = [
        1 => 'https://cdn2.iconfinder.com/data/icons/world-flag-icons/256/Flag_of_Czech_Republic.png',
        2 => 'https://cdn2.iconfinder.com/data/icons/world-flag-icons/256/Flag_of_Slovakia.png',
        3 => 'https://cdn2.iconfinder.com/data/icons/world-flag-icons/256/Flag_of_Germany.png',
        //4 => 'https://cdn2.iconfinder.com/data/icons/world-flag-icons/256/Flag_of_Italy.png',
        //5 => 'https://cdn2.iconfinder.com/data/icons/world-flag-icons/256/Flag_of_Netherlands.png',
        //6 => 'https://cdn2.iconfinder.com/data/icons/world-flag-icons/256/Flag_of_Austria.png',
        //7 => 'https://cdn2.iconfinder.com/data/icons/world-flag-icons/256/Flag_of_Poland.png',
        //8 => 'https://cdn2.iconfinder.com/data/icons/world-flag-icons/256/Flag_of_Hungary.png',
        //9 => 'https://cdn2.iconfinder.com/data/icons/world-flag-icons/256/Flag_of_England.png',
        //10 => 'https://cdn2.iconfinder.com/data/icons/world-flag-icons/256/Flag_of_Russia.png',
    ];

    private const TRANSLATED_DOMAIN_NAME_BY_DOMAIN_ID = [
        1 => 'Česko',
        2 => 'Slovensko',
        3 => 'Deutschland',
        //4 => 'Italia',
        //5 => 'Nederland',
        //6 => 'Österreich',
        //7 => 'Polska',
        //8 => 'Magyarország',
        //9 => 'Great britain',
        //10 => 'Россия',
    ];

    /**
     * @var \Symfony\Component\Templating\EngineInterface
     */
    private $templating;

    /**
     * @param mixed $domainImagesUrlPrefix
     * @param \Symfony\Component\Asset\Packages $assetPackages
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Component\Domain\DomainFacade $domainFacade
     * @param \Symfony\Component\Templating\EngineInterface $templating
     */
    public function __construct($domainImagesUrlPrefix, Packages $assetPackages, Domain $domain, DomainFacade $domainFacade, EngineInterface $templating)
    {
        parent::__construct($domainImagesUrlPrefix, $assetPackages, $domain, $domainFacade);
        $this->templating = $templating;
    }

    /**
     * @return array
     */
    public function getFunctions(): array
    {
        return array_merge(parent::getFunctions(), [
            new TwigFunction('domainSelector', [$this, 'getDomainSelector'], ['is_safe' => ['html']]),
            new TwigFunction('isGermanyDomain', [$this, 'isGermanyDomain']),
        ]);
    }

    /**
     * @return string
     */
    public function getDomainSelector(): string
    {
        $domainsToRender = [];

        foreach ($this->domain->getAll() as $domainConfig) {
            if ($domainConfig->getId() !== $this->domain->getCurrentDomainConfig()->getId()) {
                $domainsToRender[$domainConfig->getId()] = [
                    'iconUrl' => $this->getIconForDomain($domainConfig->getId()),
                    'url' => $domainConfig->getUrl(),
                    'translatedName' => $this->getTranslatedDisplayNameForDomain($domainConfig->getId()),
                ];
            }
        }

        return $this->templating->render('@ShopsysShop/Front/Inline/Common/domainSelector.html.twig', [
            'domainsToRender' => $domainsToRender,
        ]);
    }

    /**
     * @return bool
     */
    public function isGermanyDomain(): bool
    {
        return DomainHelper::isGermanDomain($this->domain);
    }

    /**
     * @param int $domainId
     * @return string
     */
    private function getIconForDomain(int $domainId): string
    {
        if (array_key_exists($domainId, self::DOMAINS_ICON_BY_DOMAIN_ID) === false) {
            throw new MissingDomainIconException(
                sprintf('Domain icon for domain with ID `%s` is missing. Please add it to DomainExtension::DOMAINS_ICON_BY_DOMAIN_ID constant', $domainId)
            );
        }

        return self::DOMAINS_ICON_BY_DOMAIN_ID[$domainId];
    }

    /**
     * @param int $domainId
     * @return string
     */
    private function getTranslatedDisplayNameForDomain(int $domainId): string
    {
        if (array_key_exists($domainId, self::TRANSLATED_DOMAIN_NAME_BY_DOMAIN_ID) === false) {
            throw new MissingDomainNameException(
                sprintf('Translated domain name for domain with ID `%s` is missing. Please add it to DomainExtension::TRANSLATED_DOMAIN_NAME_BY_DOMAIN_ID constant', $domainId)
            );
        }

        return self::TRANSLATED_DOMAIN_NAME_BY_DOMAIN_ID[$domainId];
    }
}
