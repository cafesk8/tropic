<?php declare(strict_types=1);

namespace App\Model\HeaderText;

use Shopsys\FrameworkBundle\Component\Setting\Setting;

class HeaderTextSettingFacade
{
    public const  HEADER_TITLE= 'headerTitle';
    public const HEADER_TEXT = 'headerText';
    public const HEADER_LINK = 'headerLink';

    /**
     * @var \App\Component\Setting\Setting
     */
    protected $setting;

    /**
     * @param \App\Component\Setting\Setting $setting
     */
    public function __construct(Setting $setting)
    {
        $this->setting = $setting;
    }

    /**
     * @param int $domainId
     * @return string|null
     */
    public function getHeaderTitle($domainId)
    {
        return $this->setting->getForDomain(self::HEADER_TITLE, $domainId);
    }

    /**
     * @param int $domainId
     * @return string|null
     */
    public function getHeaderText($domainId)
    {
        return $this->setting->getForDomain(self::HEADER_TEXT, $domainId);
    }

    /**
     * @param int $domainId
     * @return string|null
     */
    public function getHeaderLink($domainId)
    {
        return $this->setting->getForDomain(self::HEADER_LINK, $domainId);
    }

    /**
     * @param string|null $value
     * @param int $domainId
     */
    public function setHeaderTitle($value, $domainId)
    {
        $this->setting->setForDomain(self::HEADER_TITLE, $value, $domainId);
    }

    /**
     * @param string|null $value
     * @param int $domainId
     */
    public function setHeaderText($value, $domainId)
    {
        $this->setting->setForDomain(self::HEADER_TEXT, $value, $domainId);
    }

    /**
     * @param string|null $value
     * @param int $domainId
     */
    public function setHeaderLink($value, $domainId)
    {
        $this->setting->setForDomain(self::HEADER_LINK, $value, $domainId);
    }
}
