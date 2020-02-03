<?php

declare(strict_types=1);

namespace App\Model\Customer\TransferIds;

use App\Component\Transfer\Response\TransferResponseItemDataInterface;

class CustomerInfoResponseItemData implements TransferResponseItemDataInterface
{
    /**
     * @var float|null
     */
    private $coefficient;

    /**
     * @var \App\Model\Customer\TransferIds\UserTransferId
     */
    private $transferId;

    /**
     * @param array $responseData
     * @param \App\Model\Customer\TransferIds\UserTransferId $transferId
     */
    public function __construct(array $responseData, UserTransferId $transferId)
    {
        $this->coefficient = $this->getValidCoefficient($responseData);
        $this->transferId = $transferId;
    }

    /**
     * @param array $responseData
     * @return float|null
     */
    private function getValidCoefficient(array $responseData): ?float
    {
        if ($this->isCoeffListAttributeCorrect($responseData)) {
            return null;
        }

        $coefListAttribute = $responseData['CoeffList'][0];
        if ($this->isCoefficientAttributeCorrect($coefListAttribute)) {
            return null;
        }

        return $coefListAttribute['Coefficient'];
    }

    /**
     * @param mixed[] $responseData
     * @return bool
     */
    public function isCoeffListAttributeCorrect(array $responseData): bool
    {
        return array_key_exists('CoeffList', $responseData) === false || $responseData['CoeffList'] === null ||
            is_array($responseData['CoeffList']) === false || count($responseData['CoeffList']) === 0;
    }

    /**
     * @param mixed[] $coefListAttribute
     * @return bool
     */
    public function isCoefficientAttributeCorrect(array $coefListAttribute): bool
    {
        return array_key_exists('Coefficient', $coefListAttribute) === false || $coefListAttribute['Coefficient'] === null;
    }

    /**
     * @return float|null
     */
    public function getCoefficient(): ?float
    {
        return $this->coefficient;
    }

    /**
     * @return \App\Model\Customer\TransferIds\UserTransferId
     */
    public function getTransferId(): UserTransferId
    {
        return $this->transferId;
    }

    /**
     * @return string
     */
    public function getDataIdentifier(): string
    {
        return $this->transferId->getCustomer()->getTransferId();
    }
}
