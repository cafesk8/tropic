<?php

declare(strict_types=1);

namespace App\Component\Transfer\Pohoda\Product\Image;

use App\Component\Transfer\Pohoda\Exception\PohodaInvalidDataException;
use App\Model\Product\Product;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PohodaImageDataValidator
{
    /**
     * @var \Symfony\Component\Validator\Validator\ValidatorInterface
     */
    private $validator;

    /**
     * @param \Symfony\Component\Validator\Validator\ValidatorInterface $validator
     */
    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param array $pohodaCategoryData
     */
    public function validate(array $pohodaCategoryData): void
    {
        $violations = $this->validator->validate($pohodaCategoryData, new Collection([
            'allowExtraFields' => true,
            'fields' => [
                PohodaImage::ALIAS_POSITION => [
                    new NotBlank(),
                ],
                PohodaImage::ALIAS_ID => [
                    new NotBlank(),
                ],
                PohodaImage::ALIAS_FILE => [
                    new NotBlank(),
                ],
                PohodaImage::ALIAS_DEFAULT => [
                    new NotBlank(),
                ],
                PohodaImage::ALIAS_PRODUCT_POHODA_ID => [
                    new NotBlank(),
                ],
                PohodaImage::ALIAS_DESCRIPTION => [
                    new Callback(['callback' => [$this, 'validateDescription']]),
                ],
            ],
        ]));

        if (count($violations) > 0) {
            throw new PohodaInvalidDataException($violations);
        }
    }

    /**
     * @param string|null $imageDescription
     * @param \Symfony\Component\Validator\Context\ExecutionContextInterface $context
     */
    public function validateDescription(?string $imageDescription, ExecutionContextInterface $context): void
    {
        $invalidMessage = 'Popis obrázku pro set od dodavatele má nevalidní formát (očekává se nenulový počet znaků před i za hvězdičkou, přičemž část za hvězdičkou by měla obsahovat jen číslice)';
        if ($imageDescription !== null) {
            if ($imageDescription === '') {
                $context->addViolation($invalidMessage);
                return;
            }
            $separatorPosition = strpos($imageDescription, Product::SUPPLIER_SET_ITEM_NAME_COUNT_SEPARATOR);
            if ($separatorPosition !== false) {
                $supplierSetItemCount = substr($imageDescription, $separatorPosition + 1);
                $supplierSetItemName = substr($imageDescription, 0, $separatorPosition);
                if ((int)$supplierSetItemCount === 0
                    || strlen($supplierSetItemName) === 0
                    || !preg_match('#^\d+$#', $supplierSetItemCount)
                ) {
                    $context->addViolation($invalidMessage);
                    return;
                }
            }
        }
    }
}
