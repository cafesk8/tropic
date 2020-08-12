<?php

declare(strict_types = 1);

namespace App\Model\Order\Migration;

use Exception;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class LegacyOrderConstraintViolation extends Exception
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

        foreach ($violations as $violation) {
            $constraintsViolationsMessages[] =
                sprintf('Nevalidní hodnota pro %s - "%s"', $violation->getPropertyPath(), $violation->getMessage());
        }

        return implode(', ', $constraintsViolationsMessages);
    }
}
