<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans;

use Shopsys\ShopBundle\Component\Transfer\Response\TransferResponseItemDataInterface;

class CustomerInfoResponseItemData implements TransferResponseItemDataInterface
{
    /**
     * @var float|null
     */
    private $coefficient;

    /**
     * @var \Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\UserTransferIdAndEan
     */
    private $transferIdAndEan;

    /**
     * @param array $responseData
     * @param \Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\UserTransferIdAndEan $transferIdAndEan
     */
    public function __construct(array $responseData, UserTransferIdAndEan $transferIdAndEan)
    {
        $this->coefficient = $this->getValidCoefficient($responseData);
        $this->transferIdAndEan = $transferIdAndEan;
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
     * @return \Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\UserTransferIdAndEan
     */
    public function getTransferIdAndEan(): UserTransferIdAndEan
    {
        return $this->transferIdAndEan;
    }

    /**
     * @return string
     */
    public function getDataIdentifier(): string
    {
        return $this->transferIdAndEan->getCustomer()->getTransferId();
    }
}
