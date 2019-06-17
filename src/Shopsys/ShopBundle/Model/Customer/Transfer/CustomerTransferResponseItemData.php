<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Customer\Transfer;

use ArrayAccess;
use IteratorAggregate;
use Shopsys\FrameworkBundle\Component\String\TransformString;
use Shopsys\ShopBundle\Component\DataObject\ReadObjectAsArrayTrait;
use Shopsys\ShopBundle\Component\Transfer\Response\TransferResponseItemDataInterface;

class CustomerTransferResponseItemData implements TransferResponseItemDataInterface, ArrayAccess, IteratorAggregate
{
    use ReadObjectAsArrayTrait;

    /**
     * @var string
     */
    private $transferId;

    /**
     * @var string|null
     */
    private $firstName;

    /**
     * @var string|null
     */
    private $lastName;

    /**
     * @var string|null
     */
    private $branchNumber;

    /**
     * @var string|null
     */
    private $email;

    /**
     * @var string|null
     */
    private $phone;

    /**
     * @var string|null
     */
    private $countryCode;

    /**
     * @param array $restData
     */
    public function __construct(array $restData)
    {
        $address = $restData['Adress'];
        $this->transferId = $restData['ID'] !== null ? TransformString::emptyToNull(trim($restData['ID'])) : null;
        $this->firstName = $address['ForeName'] !== null ? TransformString::emptyToNull(trim($address['ForeName'])) : null;
        $this->lastName = $address['SureName'] !== null ? TransformString::emptyToNull(trim($address['SureName'])) : null;
        $this->branchNumber = $address['BranchNumber'] !== null ? TransformString::emptyToNull(trim($address['BranchNumber'])) : null;
        $this->email = $restData['Email'] !== null ? TransformString::emptyToNull(trim($restData['Email'])) : null;
        $this->phone = $restData['Phone'] !== null ? TransformString::emptyToNull(trim($restData['Phone'])) : null;
        $this->countryCode = $address['Country'] !== null ? TransformString::emptyToNull(trim($address['Country'])) : null;
    }

    /**
     * @return string
     */
    public function getDataIdentifier(): string
    {
        return $this->transferId;
    }

    /**
     * @return string|null
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * @return string|null
     */
    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    /**
     * @return string|null
     */
    public function getBranchNumber(): ?string
    {
        return $this->branchNumber;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @return string|null
     */
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * @return string|null
     */
    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }
}
