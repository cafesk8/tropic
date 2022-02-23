<?php

declare(strict_types=1);

namespace App\Model\Product;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Product\Product as BaseProduct;
use Shopsys\FrameworkBundle\Model\Product\ProductDomain as BaseProductDomain;

/**
 * @ORM\Table(name="product_domains")
 * @ORM\Entity
 * @property \App\Model\Product\Product $product
 * @property \App\Model\Pricing\Vat\Vat $vat
 * @method \App\Model\Pricing\Vat\Vat getVat()
 * @method setVat(\App\Model\Pricing\Vat\Vat $vat)
 */
class ProductDomain extends BaseProductDomain
{
    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    protected $generateToMergadoXmlFeed;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $descriptionHash;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $shortDescriptionHash;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $shown;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $nameForMergadoFeed = null;

    /**
     * @ORM\Column(type="money", precision=20, scale=6, nullable=true)
     */
    private ?Money $transportFee;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $exportedToLuigisBox;

    /**
     * @ORM\Column(type="boolean", nullable=false)
     */
    private bool $registrationDiscountDisabled;

    /**
     * @ORM\Column(type="boolean", nullable=false)
     */
    private bool $promoDiscountDisabled;

    /**
     * @param \App\Model\Product\Product $product
     * @param int $domainId
     */
    public function __construct(BaseProduct $product, int $domainId)
    {
        parent::__construct($product, $domainId);

        $this->generateToMergadoXmlFeed = true;
        $this->descriptionHash = null;
        $this->shortDescriptionHash = null;
        $this->shown = true;
        $this->transportFee = null;
        $this->exportedToLuigisBox = false;
        $this->registrationDiscountDisabled = false;
        $this->promoDiscountDisabled = false;
    }

    /**
     * @return bool
     */
    public function isGenerateToMergadoXmlFeed(): bool
    {
        return $this->generateToMergadoXmlFeed;
    }

    /**
     * @param bool $isGenerateToMergadoXmlFeed
     */
    public function setGenerateToMergadoXmlFeed(bool $isGenerateToMergadoXmlFeed): void
    {
        $this->generateToMergadoXmlFeed = $isGenerateToMergadoXmlFeed;
    }

    /**
     * @return string|null
     */
    public function getDescriptionHash(): ?string
    {
        return $this->descriptionHash;
    }

    /**
     * @param string|null $descriptionHash
     */
    public function setDescriptionHash(?string $descriptionHash): void
    {
        $this->descriptionHash = $descriptionHash;
    }

    /**
     * @return string|null
     */
    public function getShortDescriptionHash(): ?string
    {
        return $this->shortDescriptionHash;
    }

    /**
     * @param string|null $shortDescriptionHash
     */
    public function setShortDescriptionHash(?string $shortDescriptionHash): void
    {
        $this->shortDescriptionHash = $shortDescriptionHash;
    }

    /**
     * @return \App\Model\Product\Product
     */
    public function getProduct(): Product
    {
        return $this->product;
    }

    /**
     * @return bool
     */
    public function isShown(): bool
    {
        return $this->shown;
    }

    /**
     * @param bool $shown
     */
    public function setShown(bool $shown): void
    {
        $this->shown = $shown;
    }

    /**
     * @return string|null
     */
    public function getNameForMergadoFeed(): ?string
    {
        return $this->nameForMergadoFeed;
    }

    /**
     * @param string|null $nameForMergadoFeed
     */
    public function setNameForMergadoFeed(?string $nameForMergadoFeed): void
    {
        $this->nameForMergadoFeed = $nameForMergadoFeed;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Component\Money\Money|null
     */
    public function getTransportFee(): ?Money
    {
        return $this->transportFee;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Money\Money|null $transportFee
     */
    public function setTransportFee(?Money $transportFee): void
    {
        $this->transportFee = $transportFee;
    }

    /**
     * @return bool
     */
    public function isExportedToLuigisBox(): bool
    {
        return $this->exportedToLuigisBox;
    }

    /**
     * @param bool $exportedToLuigisBox
     */
    public function setExportedToLuigisBox(bool $exportedToLuigisBox): void
    {
        $this->exportedToLuigisBox = $exportedToLuigisBox;
    }

    /**
     * @return bool
     */
    public function isRegistrationDiscountDisabled(): bool
    {
        return $this->registrationDiscountDisabled;
    }

    /**
     * @param bool $registrationDiscountDisabled
     */
    public function setRegistrationDiscountDisabled(bool $registrationDiscountDisabled): void
    {
        $this->registrationDiscountDisabled = $registrationDiscountDisabled;
    }

    /**
     * @return bool
     */
    public function isPromoDiscountDisabled(): bool
    {
        return $this->promoDiscountDisabled;
    }

    /**
     * @param bool $promoDiscountDisabled
     */
    public function setPromoDiscountDisabled(bool $promoDiscountDisabled): void
    {
        $this->promoDiscountDisabled = $promoDiscountDisabled;
    }
}
