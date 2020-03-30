<?php

declare(strict_types=1);

namespace App\Component\DiscountExclusion;

use App\Component\Setting\Setting;
use Shopsys\FrameworkBundle\Component\Domain\Domain;

class DiscountExclusionFacade
{
    public const SETTING_REGISTRATION_DISCOUNT_EXCLUSION = 'registrationDiscountText';

    /**
     * @var \App\Component\Setting\Setting
     */
    private $setting;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Component\Setting\Setting $setting
     */
    public function __construct(Domain $domain, Setting $setting)
    {
        $this->setting = $setting;
        $this->domain = $domain;
    }

    /**
     * @param int $domainId
     * @return string
     */
    public function getRegistrationDiscountExclusionText(int $domainId): string
    {
        return $this->setting->getForDomain(self::SETTING_REGISTRATION_DISCOUNT_EXCLUSION, $domainId);
    }

    /**
     * @return string[]
     */
    public function getRegistrationDiscountExclusionTexts(): array
    {
        $registrationDiscountExclusionTexts = [];

        foreach ($this->domain->getAllIds() as $domainId) {
            $registrationDiscountExclusionTexts[$domainId] = $this->getRegistrationDiscountExclusionText($domainId);
        }

        return $registrationDiscountExclusionTexts;
    }

    /**
     * @param string $text
     * @param int $domainId
     */
    public function setRegistrationDiscountExclusionText(string $text, int $domainId): void
    {
        $this->setting->setForDomain(self::SETTING_REGISTRATION_DISCOUNT_EXCLUSION, $text, $domainId);
    }
}
