<?php

declare(strict_types=1);

namespace App\Model\Product\Flag;

use App\Component\Setting\Setting;
use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Model\Product\Flag\FlagFacade as BaseFlagFacade;
use Shopsys\FrameworkBundle\Model\Product\Flag\FlagFactory;
use Shopsys\FrameworkBundle\Model\Product\Flag\FlagRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @method \App\Model\Product\Flag\Flag getById(int $flagId)
 * @method \App\Model\Product\Flag\Flag create(\App\Model\Product\Flag\FlagData $flagData)
 * @method \App\Model\Product\Flag\Flag edit(int $flagId, \App\Model\Product\Flag\FlagData $flagData)
 * @method \App\Model\Product\Flag\Flag[] getAll()
 * @method dispatchFlagEvent(\App\Model\Product\Flag\Flag $flag, string $eventType)
 */
class FlagFacade extends BaseFlagFacade
{
    /**
     * @var \App\Component\Setting\Setting
     */
    private $setting;

    /**
     * @var \App\Model\Product\Flag\FlagRepository
     */
    protected $flagRepository;

    /**
     * @var \App\Model\Product\Flag\ProductFlagFacade
     */
    private $productFlagFacade;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \App\Model\Product\Flag\FlagRepository $flagRepository
     * @param \Shopsys\FrameworkBundle\Model\Product\Flag\FlagFactory $flagFactory
     * @param \App\Component\Setting\Setting $setting
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
     * @param \App\Model\Product\Flag\ProductFlagFacade $productFlagFacade
     */
    public function __construct(
        EntityManagerInterface $em,
        FlagRepository $flagRepository,
        FlagFactory $flagFactory,
        Setting $setting,
        EventDispatcherInterface $eventDispatcher,
        ProductFlagFacade $productFlagFacade
    ) {
        parent::__construct($em, $flagRepository, $flagFactory, $eventDispatcher);
        $this->setting = $setting;
        $this->productFlagFacade = $productFlagFacade;
    }

    /**
     * @param int|null $flagId
     */
    public function setDefaultFlagForFreeTransportAndPayment(?int $flagId): void
    {
        $this->setting->set(Setting::FREE_TRANSPORT_FLAG, $flagId);
    }

    /**
     * @throws \Shopsys\FrameworkBundle\Component\Setting\Exception\SettingValueNotFoundException
     * @return \App\Model\Product\Flag\Flag|null
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
        $flag = $this->getById($flagId);
        $this->productFlagFacade->deleteByFlag($flag);

        $defaultFlagForFreeTransportAndPayment = $this->getDefaultFlagForFreeTransportAndPayment();
        if ($defaultFlagForFreeTransportAndPayment !== null && $flagId === $defaultFlagForFreeTransportAndPayment->getId()) {
            $this->setDefaultFlagForFreeTransportAndPayment(null);
        }

        parent::deleteById($flagId);
    }

    /**
     * @return \App\Model\Product\Flag\Flag[]
     */
    public function getAllExceptFreeTransportFlag(): array
    {
        return $this->flagRepository->getAllExceptIds([
            $this->setting->get(Setting::FREE_TRANSPORT_FLAG),
        ]);
    }

    /**
     * @return \App\Model\Product\Flag\Flag[]
     */
    public function getSaleFlags(): array
    {
        return $this->flagRepository->getSaleFlags();
    }

    /**
     * @return \App\Model\Product\Flag\Flag[]
     */
    public function getNewsFlags(): array
    {
        return $this->flagRepository->getNewsFlags();
    }

    /**
     * @return \App\Model\Product\Flag\Flag[]
     */
    public function getAllIndexedByPohodaId(): array
    {
        $flags = $this->flagRepository->getAll();
        $flagsIndexedByPohodaId = [];

        foreach ($flags as $flag) {
            if ($flag->getPohodaId() !== null) {
                $flagsIndexedByPohodaId[$flag->getPohodaId()] = $flag;
            }
        }

        return $flagsIndexedByPohodaId;
    }
}
