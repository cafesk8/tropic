<?php

declare(strict_types=1);

namespace App\Component\Transfer\Pohoda\Product;

use App\Component\Transfer\Pohoda\Exception\PohodaInvalidDataException;
use App\Model\Product\Product;
use App\Model\Product\ProductVariantTropicFacade;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PohodaProductDataValidator
{
    /**
     * @var \Symfony\Component\Validator\Validator\ValidatorInterface
     */
    private $validator;

    /**
     * @var \App\Model\Product\ProductVariantTropicFacade
     */
    private $productVariantTropicFacade;

    /**
     * @var string[]
     */
    private $variantIdsCache;

    /**
     * @param \Symfony\Component\Validator\Validator\ValidatorInterface $validator
     * @param \App\Model\Product\ProductVariantTropicFacade $productVariantTropicFacade
     */
    public function __construct(ValidatorInterface $validator, ProductVariantTropicFacade $productVariantTropicFacade)
    {
        $this->validator = $validator;
        $this->productVariantTropicFacade = $productVariantTropicFacade;
        $this->variantIdsCache = [];
    }

    /**
     * @param array $pohodaProductData
     */
    public function validate(array $pohodaProductData): void
    {
        $violations = $this->validator->validate($pohodaProductData, new Collection([
            'allowExtraFields' => true,
            'fields' => [
                PohodaProduct::COL_POHODA_ID => [
                    new Type(['type' => 'numeric']),
                    new NotBlank(),
                ],
                PohodaProduct::COL_CATNUM => [
                    new Type(['type' => 'string']),
                    new NotBlank(),
                ],
                PohodaProduct::COL_NAME => [
                    new Type(['type' => 'string']),
                    new NotBlank(),
                ],
                // It is a string with 0 or 1 so we cannot validate bool here
                PohodaProduct::COL_REGISTRATION_DISCOUNT_DISABLED => [
                    new NotBlank(),
                ],
                PohodaProduct::COL_PROMO_DISCOUNT_DISABLED => [
                    new NotBlank(),
                ],
                PohodaProduct::COL_SELLING_PRICE => [
                    new Type(['type' => 'numeric']),
                    new NotBlank(),
                ],
                PohodaProduct::COL_SELLING_VAT_RATE_ID => [
                    new Type(['type' => 'numeric']),
                    new NotBlank(),
                ],
                PohodaProduct::COL_SALE_INFORMATION => [
                    new Type(['type' => 'array']),
                ],
                PohodaProduct::COL_VARIANT_ID => [
                    new Callback(['callback' => [$this, 'validateVariantId'], 'payload' => $pohodaProductData]),
                ],
                PohodaProduct::COL_AUTO_EUR_PRICE => [
                    new NotBlank(),
                ],
                PohodaProduct::COL_POHODA_PRODUCT_MINIMUM_AMOUNT_AND_MULTIPLIER => [
                    new Type(['type' => 'numeric']),
                    new NotBlank(),
                ],
                PohodaProduct::COL_POHODA_PRODUCT_BRAND_NAME => [
                    new Type(['type' => 'string']),
                ],
                PohodaProduct::COL_POHODA_PRODUCT_WARRANTY => [
                    new Type(['type' => 'numeric']),
                ],
                PohodaProduct::COL_POHODA_PRODUCT_UNIT => [
                    new Type(['type' => 'string']),
                    new NotBlank(),
                ],
                PohodaProduct::COL_POHODA_PRODUCT_EAN => [
                    new Type(['type' => 'string']),
                ],
            ],
        ]));

        if (count($violations) > 0) {
            throw new PohodaInvalidDataException($violations);
        } else {
            $this->variantIdsCache[] = $pohodaProductData[PohodaProduct::COL_VARIANT_ID];
        }
    }

    /**
     * @param string|null $variantId
     * @param \Symfony\Component\Validator\Context\ExecutionContextInterface $context
     * @param array $pohodaProductData
     */
    public function validateVariantId(?string $variantId, ExecutionContextInterface $context, array $pohodaProductData): void
    {
        if ($this->productVariantTropicFacade->isVariant($variantId)) {
            $mainVariantVariantId = Product::getMainVariantVariantIdFromVariantVariantId($variantId);
            $variantNumber = Product::getVariantNumber($variantId);
            if (strlen($mainVariantVariantId) === 0
                || strlen($variantNumber) === 0
                || !preg_match('#^\d+$#', $variantNumber)
            ) {
                $context->addViolation(
                    'Zadané ID modifikace má neplatný formát (očekává se nenulový počet znaků před i za lomítkem, přičemž část za lomítkem by měla obsahovat jen číslice)'
                );
                return;
            }
        }

        if ($variantId !== null) {
            $existingProductByVariantId = $this->productVariantTropicFacade->findByVariantId($variantId);
            if (($existingProductByVariantId !== null
                    && $existingProductByVariantId->getPohodaId() !== (int)$pohodaProductData[PohodaProduct::COL_POHODA_ID])
                || in_array($pohodaProductData[PohodaProduct::COL_VARIANT_ID], $this->variantIdsCache, true)
            ) {
                $context->addViolation('Zadané ID modifikace je již v systému přiřazeno jinému produktu');
            }
        }

        if ($this->productVariantTropicFacade->isVariant($variantId)
            && $this->productVariantTropicFacade->findMainVariantByVariantId($variantId) === null
        ) {
            $context->addViolation('Není možné vyvořit variantu, pro kterou neexistuje odpovídající hlavní varianta');
        }
    }
}
