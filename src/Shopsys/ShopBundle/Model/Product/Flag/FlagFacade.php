<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Flag;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Model\Product\Flag\FlagFacade as BaseFlagFacade;
use Shopsys\FrameworkBundle\Model\Product\Flag\FlagFactory;
use Shopsys\FrameworkBundle\Model\Product\Flag\FlagRepository;
use Shopsys\ShopBundle\Component\Setting\Setting;

class FlagFacade extends BaseFlagFacade
{
    /**
     * @var \Shopsys\ShopBundle\Component\Setting\Setting
     */
    private $setting;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\FrameworkBundle\Model\Product\Flag\FlagRepository $flagRepository
     * @param \Shopsys\FrameworkBundle\Model\Product\Flag\FlagFactory $flagFactory
     * @param \Shopsys\ShopBundle\Component\Setting\Setting $setting
     */
    public function __construct(EntityManagerInterface $em, FlagRepository $flagRepository, FlagFactory $flagFactory, Setting $setting)
    {
        parent::__construct($em, $flagRepository, $flagFactory);
        $this->setting = $setting;
    }

    /**
     * @param int|null $flagId
     * @param \Shopsys\ShopBundle\Model\Product\Flag\Flag $flag
     */
    public function setDefaultFlagForFreeTransportAndPayment(?int $flagId): void
    {
        $this->setting->set(Setting::FREE_TRANSPORT_FLAG, $flagId);
    }

    /**
     * @throws \Shopsys\FrameworkBundle\Component\Setting\Exception\SettingValueNotFoundException
     * @return \Shopsys\ShopBundle\Model\Product\Flag\Flag|null
     */
    public function getDefaultFlagForFreeTransportAndPayment(): ?Flag
    {
        $flagId = $this->setting->get(Setting::FREE_TRANSPORT_FLAG);

        return $flagId === null ? null : $this->flagRepository->findById($flagId);
    }

    /**
     * @param int $flagId
     */
    public function deleteById($flagId): void
    {
        $defaultFlagForFreeTransportAndPayment = $this->getDefaultFlagForFreeTransportAndPayment();
        if ($defaultFlagForFreeTransportAndPayment !== null && $flagId === $defaultFlagForFreeTransportAndPayment->getId()) {
            $this->setDefaultFlagForFreeTransportAndPayment(null);
        }

        parent::deleteById($flagId);
    }
}
