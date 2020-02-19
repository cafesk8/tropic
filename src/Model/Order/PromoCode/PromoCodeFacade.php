<?php

declare(strict_types=1);

namespace App\Model\Order\PromoCode;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\String\HashGenerator;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCodeData as BasePromoCodeData;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCodeFacade as BasePromoCodeFacade;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCodeFactoryInterface;

/**
 * @property \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator $em
 * @method \App\Model\Order\PromoCode\PromoCode getById(int $promoCodeId)
 * @method \App\Model\Order\PromoCode\PromoCode|null findPromoCodeByCode(string $code)
 * @method \App\Model\Order\PromoCode\PromoCode[] getAll()
 */
class PromoCodeFacade extends BasePromoCodeFacade
{
    private const MASS_CREATE_BATCH_SIZE = 200;

    /**
     * @var \App\Model\Order\PromoCode\PromoCodeRepository
     */
    protected $promoCodeRepository;

    /**
     * @var \App\Component\String\HashGenerator
     */
    private $hashGenerator;

    /**
     * @var \App\Model\Order\PromoCode\PromoCodeLimitFacade
     */
    private $promoCodeLimitFacade;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \App\Model\Order\PromoCode\PromoCodeRepository $promoCodeRepository
     * @param \Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCodeFactoryInterface $promoCodeFactory
     * @param \App\Component\String\HashGenerator $hashGenerator
     * @param \App\Model\Order\PromoCode\PromoCodeLimitFacade $promoCodeLimitFacade
     */
    public function __construct(
        EntityManagerInterface $em,
        PromoCodeRepository $promoCodeRepository,
        PromoCodeFactoryInterface $promoCodeFactory,
        HashGenerator $hashGenerator,
        PromoCodeLimitFacade $promoCodeLimitFacade
    ) {
        parent::__construct($em, $promoCodeRepository, $promoCodeFactory);
        $this->hashGenerator = $hashGenerator;
        $this->promoCodeLimitFacade = $promoCodeLimitFacade;
    }

    /**
     * @param \App\Model\Order\PromoCode\PromoCodeData $promoCodeData
     * @return \App\Model\Order\PromoCode\PromoCode
     */
    public function create(BasePromoCodeData $promoCodeData)
    {
        /** @var \App\Model\Order\PromoCode\PromoCode $promoCode */
        $promoCode = parent::create($promoCodeData);
        $this->refreshPromoCodeLimits($promoCode, $promoCodeData);

        return $promoCode;
    }

    /**
     * @param int $promoCodeId
     * @param \App\Model\Order\PromoCode\PromoCodeData $promoCodeData
     * @return \App\Model\Order\PromoCode\PromoCode
     */
    public function edit($promoCodeId, BasePromoCodeData $promoCodeData)
    {
        /** @var \App\Model\Order\PromoCode\PromoCode $promoCode */
        $promoCode = parent::edit($promoCodeId, $promoCodeData);
        $this->refreshPromoCodeLimits($promoCode, $promoCodeData);

        return $promoCode;
    }

    /**
     * @param \App\Model\Order\PromoCode\PromoCode $promoCode
     * @param \App\Model\Order\PromoCode\PromoCodeData $promoCodeData
     */
    private function refreshPromoCodeLimits(PromoCode $promoCode, PromoCodeData $promoCodeData)
    {
        $this->promoCodeLimitFacade->refreshLimits($promoCode, $promoCodeData);
    }

    /**
     * @param \App\Model\Order\PromoCode\PromoCode $promoCode
     */
    public function usePromoCode(PromoCode $promoCode): void
    {
        $promoCode->addUsage();
        $this->em->flush($promoCode);
    }

    /**
     * @param \App\Model\Order\PromoCode\PromoCodeData $promoCodeData
     */
    public function massCreate(PromoCodeData $promoCodeData): void
    {
        $existingPromoCodeCodes = $this->promoCodeRepository->getAllPromoCodeCodes();
        $generatedPromoCodeCount = 0;
        $toFlush = [];

        while ($generatedPromoCodeCount < $promoCodeData->quantity) {
            $code = $promoCodeData->prefix . strtoupper($this->hashGenerator->generateHashWithoutConfusingCharacters(PromoCode::MASS_GENERATED_CODE_LENGTH));

            if (!in_array($code, $existingPromoCodeCodes, true)) {
                $promoCodeData->code = $code;

                $promoCode = new PromoCode($promoCodeData);
                $this->em->persist($promoCode);
                $toFlush[] = $promoCode;

                $existingPromoCodeCodes[] = $code;
                $generatedPromoCodeCount++;
            }

            if ($generatedPromoCodeCount % self::MASS_CREATE_BATCH_SIZE === 0) {
                $this->flushAndClear($toFlush);
                $toFlush = [];
            }
        }

        $this->flushAndClear($toFlush);
    }

    /**
     * @param \App\Model\Order\PromoCode\PromoCode[] $promoCodesForFlush
     */
    private function flushAndClear(array $promoCodesForFlush): void
    {
        $this->em->flush($promoCodesForFlush);
        $this->em->clear();
    }

    /**
     * @param string $prefix
     */
    public function deleteByPrefix(string $prefix): void
    {
        $this->promoCodeRepository->deleteByPrefix($prefix);
    }
}