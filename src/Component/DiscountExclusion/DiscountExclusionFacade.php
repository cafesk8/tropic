<?php

declare(strict_types=1);

namespace App\Component\DiscountExclusion;

use App\Component\Setting\Setting;
use Shopsys\FrameworkBundle\Component\Domain\Domain;

class DiscountExclusionFacade
{
    public const SETTING_REGISTRATION_DISCOUNT_EXCLUSION = 'registrationDiscountText';
    public const SETTING_PROMO_DISCOUNT_EXCLUSION = 'promoDiscountText';
    public const SETTING_ALL_DISCOUNT_EXCLUSION = 'allDiscountText';

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
     * @return string[]
     */
    public function getRegistrationDiscountExclusionTexts(): array
    {
        return $this->getDiscountExclusionTexts(self::SETTING_REGISTRATION_DISCOUNT_EXCLUSION);
    }

    /**
     * @return string[]
     */
    public function getPromoDiscountExclusionTexts(): array
    {
        return $this->getDiscountExclusionTexts(self::SETTING_PROMO_DISCOUNT_EXCLUSION);
    }

    /**
     * @return string[]
     */
    public function getAllDiscountExclusionTexts(): array
    {
        return $this->getDiscountExclusionTexts(self::SETTING_ALL_DISCOUNT_EXCLUSION);
    }

    /**
     * @param string $text
     * @param int $domainId
     */
    public function setRegistrationDiscountExclusionText(string $text, int $domainId): void
    {
        $this->setting->setForDomain(self::SETTING_REGISTRATION_DISCOUNT_EXCLUSION, $text, $domainId);
    }

    /**
     * @param string $text
     * @param int $domainId
     */
    public function setPromoDiscountExclusionText(string $text, int $domainId): void
    {
        $this->setting->setForDomain(self::SETTING_PROMO_DISCOUNT_EXCLUSION, $text, $domainId);
    }

    /**
     * @param string $text
     * @param int $domainId
     */
    public function setAllDiscountExclusionText(string $text, int $domainId): void
    {
        $this->setting->setForDomain(self::SETTING_ALL_DISCOUNT_EXCLUSION, $text, $domainId);
    }

    /**
     * @param int $domainId
     * @return string
     */
    public function getRegistrationDiscountExclusionText(int $domainId): string
    {
        return $this->getDiscountExclusionText(self::SETTING_REGISTRATION_DISCOUNT_EXCLUSION, $domainId);
    }

    /**
     * @param int $domainId
     * @return string
     */
    public function getPromoDiscountExclusionText(int $domainId): string
    {
        return $this->getDiscountExclusionText(self::SETTING_PROMO_DISCOUNT_EXCLUSION, $domainId);
    }

    /**
     * @param int $domainId
     * @return string
     */
    public function getAllDiscountExclusionText(int $domainId): string
    {
        return $this->getDiscountExclusionText(self::SETTING_ALL_DISCOUNT_EXCLUSION, $domainId);
    }

    /**
     * @param string $exclusionType
     * @param int $domainId
     * @return string
     */
    private function getDiscountExclusionText(string $exclusionType, int $domainId): string
    {
        return $this->setting->getForDomain($exclusionType, $domainId);
    }

    /**
     * @param string $exclusionType
     * @return string[]
     */
    private function getDiscountExclusionTexts(string $exclusionType): array
    {
        $registrationDiscountExclusionTexts = [];

        foreach ($this->domain->getAllIds() as $domainId) {
            $registrationDiscountExclusionTexts[$domainId] = $this->getDiscountExclusionText($exclusionType, $domainId);
        }

        return $registrationDiscountExclusionTexts;
    }
}
