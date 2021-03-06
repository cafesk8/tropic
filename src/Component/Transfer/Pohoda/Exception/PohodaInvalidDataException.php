<?php

declare(strict_types=1);

namespace App\Component\Transfer\Pohoda\Exception;

use Exception;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class PohodaInvalidDataException extends Exception implements PohodaTransferExceptionInterface
{
    /**
     * @param \Symfony\Component\Validator\ConstraintViolationListInterface $violations
     * @param \Exception|null $previous
     */
    public function __construct(
        ConstraintViolationListInterface $violations,
        ?Exception $previous = null
    ) {
        $message = $this->getViolationsAsString($violations);

        parent::__construct($message, 0, $previous);
    }

    /**
     * @param \Symfony\Component\Validator\ConstraintViolationListInterface $violations
     * @return string
     */
    private function getViolationsAsString(ConstraintViolationListInterface $violations): string
    {
        $constraintsViolationsMessages = [];

        /** @var \Symfony\Component\Validator\ConstraintViolationInterface $violation */
        foreach ($violations as $violation) {
            $constraintsViolationsMessages[] =
                sprintf('Nevalidní hodnota pro %s - "%s"', $violation->getPropertyPath(), $violation->getMessage());
        }

        return implode(', ', $constraintsViolationsMessages);
    }
}
