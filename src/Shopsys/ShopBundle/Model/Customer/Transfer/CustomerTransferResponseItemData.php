<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Customer\Transfer;

use ArrayAccess;
use IteratorAggregate;
use Shopsys\FrameworkBundle\Component\String\TransformString;
use Shopsys\ShopBundle\Component\DataObject\ReadObjectAsArrayTrait;
use Shopsys\ShopBundle\Component\Domain\DomainHelper;
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
     * @var string|null
     */
    private $street;

    /**
     * @var string|null
     */
    private $city;

    /**
     * @var string|null
     */
    private $postcode;

    /**
     * @var string|null
     */
    private $companyName;

    /**
     * @var string|null
     */
    private $companyNumber;

    /**
     * @var string|null
     */
    private $companyTaxNumber;

    /**
     * @var string[]
     */
    private $eans;

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
        $this->street = $address['Street'] !== null ? TransformString::emptyToNull(trim($address['Street'])) : null;
        $this->city = $address['City'] !== null ? TransformString::emptyToNull(trim($address['City'])) : null;
        $this->postcode = $address['ZIP'] !== null ? TransformString::emptyToNull(trim($address['ZIP'])) : null;
        $this->companyName = $address['Company'] !== null ? TransformString::emptyToNull(trim($address['Company'])) : null;
        $this->companyNumber = $restData['ICO'] !== null ? TransformString::emptyToNull(trim($restData['ICO'])) : null;
        $this->companyTaxNumber = $restData['DIC'] !== null ? TransformString::emptyToNull(trim($restData['DIC'])) : null;
        $this->eans = $restData['IdCards'];
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

    /**
     * @return int|null
     */
    public function getDomainId(): ?int
    {
        return $this->countryCode !== null ? DomainHelper::DOMAIN_ID_BY_COUNTRY_CODE[$this->getCountryCode()] : null;
    }

    /**
     * @return string|null
     */
    public function getStreet(): ?string
    {
        return $this->street;
    }

    /**
     * @return string|null
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * @return string|null
     */
    public function getPostcode(): ?string
    {
        return $this->postcode;
    }

    /**
     * @return string|null
     */
    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    /**
     * @return string|null
     */
    public function getCompanyNumber(): ?string
    {
        return $this->companyNumber;
    }

    /**
     * @return string|null
     */
    public function getCompanyTaxNumber(): ?string
    {
        return $this->companyTaxNumber;
    }

    /**
     * @return string[]
     */
    public function getEans(): array
    {
        return $this->eans;
    }
}
