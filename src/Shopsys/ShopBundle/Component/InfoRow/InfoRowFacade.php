<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\InfoRow;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Setting\Setting;
use Symfony\Component\HttpFoundation\RequestStack;

class InfoRowFacade
{
    public const COOKIE_CLOSED_AT = 'infoRow_closedAt';
    public const COOKIE_CLOSED_AT_FORMAT = DateTimeInterface::ATOM;

    private const SETTING_KEY_VISIBILITY = 'infoRowVisibility';
    private const SETTING_KEY_TEXT = 'infoRowText';
    private const SETTING_KEY_LAST_CHANGE_AT = 'infoRowLastChangeAt';

    /**
     * @var \Shopsys\FrameworkBundle\Component\Setting\Setting
     */
    protected $setting;

    /**
     * @var \Symfony\Component\HttpFoundation\RequestStack
     */
    private $requestStack;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Setting\Setting $setting
     * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(
        Setting $setting,
        RequestStack $requestStack,
        Domain $domain
    ) {
        $this->setting = $setting;
        $this->requestStack = $requestStack;
        $this->domain = $domain;
    }

    /**
     * @param int $domainId
     * @return bool
     */
    public function isRowVisible(int $domainId): bool
    {
        return $this->setting->getForDomain(self::SETTING_KEY_VISIBILITY, $domainId);
    }

    /**
     * @return bool
     */
    public function isRowVisibleForCurrentCustomer(): bool
    {
        $currentDomainId = $this->domain->getId();
        if ($this->isRowVisible($currentDomainId) === false) {
            return false;
        }

        $cookieClosedAt = $this->requestStack->getMasterRequest()->cookies->get(self::COOKIE_CLOSED_AT);
        if ($cookieClosedAt === null) {
            return true;
        }

        $closedAt = DateTimeImmutable::createFromFormat(self::COOKIE_CLOSED_AT_FORMAT, $cookieClosedAt);
        $lastChangeAt = $this->setting->getForDomain(self::SETTING_KEY_LAST_CHANGE_AT, $currentDomainId);

        return $closedAt === false || $closedAt < $lastChangeAt;
    }

    /**
     * @param int $domainId
     * @return string|null
     */
    public function getRowText(int $domainId): ?string
    {
        return $this->setting->getForDomain(self::SETTING_KEY_TEXT, $domainId);
    }

    /**
     * @param bool $visibility
     * @param string|null $text
     * @param int $domainId
     */
    public function setInfoRow(bool $visibility, ?string $text, int $domainId): void
    {
        $originVisibility = $this->isRowVisible($domainId);
        $originText = $this->getRowText($domainId);

        $this->setting->setForDomain(self::SETTING_KEY_VISIBILITY, $visibility, $domainId);
        $this->setting->setForDomain(self::SETTING_KEY_TEXT, $text, $domainId);

        if ($originVisibility !== $visibility || $originText !== $text) {
            $this->setting->setForDomain(self::SETTING_KEY_LAST_CHANGE_AT, new DateTime(), $domainId);
        }
    }
}
