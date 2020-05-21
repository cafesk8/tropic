<?php

declare(strict_types=1);

namespace App\Model\Product;

use Doctrine\ORM\Mapping as ORM;
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
     * @param \App\Model\Product\Product $product
     * @param int $domainId
     */
    public function __construct(BaseProduct $product, int $domainId)
    {
        parent::__construct($product, $domainId);

        $this->generateToMergadoXmlFeed = true;
        $this->descriptionHash = null;
        $this->shortDescriptionHash = null;
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
}
