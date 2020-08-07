<?php

declare(strict_types=1);

namespace App\Component\Transfer\Pohoda\Product\Image;

use App\Component\Transfer\Pohoda\Exception\PohodaInvalidDataException;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\NotBlank;
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
            ],
        ]));

        if (count($violations) > 0) {
            throw new PohodaInvalidDataException($violations);
        }
    }
}
