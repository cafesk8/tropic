<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\PromoCode;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\String\HashGenerator;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCodeFacade as BasePromoCodeFacade;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCodeFactoryInterface;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCodeRepository;

/**
 * @method \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode getById($promoCodeId)
 */
class PromoCodeFacade extends BasePromoCodeFacade
{
    private const MASS_CREATE_BATCH_SIZE = 200;

    /**
     * @var \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeRepository
     */
    protected $promoCodeRepository;

    /**
     * @var \Shopsys\ShopBundle\Component\String\HashGenerator
     */
    private $hashGenerator;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCodeRepository $promoCodeRepository
     * @param \Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCodeFactoryInterface $promoCodeFactory
     * @param \Shopsys\FrameworkBundle\Component\String\HashGenerator $hashGenerator
     */
    public function __construct(
        EntityManagerInterface $em,
        PromoCodeRepository $promoCodeRepository,
        PromoCodeFactoryInterface $promoCodeFactory,
        HashGenerator $hashGenerator
    ) {
        parent::__construct($em, $promoCodeRepository, $promoCodeFactory);
        $this->hashGenerator = $hashGenerator;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode $promoCode
     */
    public function usePromoCode(PromoCode $promoCode): void
    {
        $promoCode->addUsage();
        $this->em->flush($promoCode);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeData $promoCodeData
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
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode[] $promoCodesForFlush
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
