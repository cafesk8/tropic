<?php

declare(strict_types=1);

namespace App\Model\Product;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Model\Product\Product;
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
     * @param \App\Model\Product\Product $product
     * @param int $domainId
     */
    public function __construct(Product $product, int $domainId)
    {
        parent::__construct($product, $domainId);

        $this->generateToMergadoXmlFeed = true;
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
    public function setGenerateToMergadoXmlFeed($isGenerateToMergadoXmlFeed): void
    {
        $this->generateToMergadoXmlFeed = $isGenerateToMergadoXmlFeed;
    }
}
