<?php

declare(strict_types=1);

namespace App\Twig;

use App\Model\Product\Product;
use App\Model\Transport\DeliveryDate\Exception\NoVisibleTransportsWithoutPickUpPlacesOnDomainException;
use App\Model\Transport\Transport;
use DateTime;
use Shopsys\FrameworkBundle\Model\Product\ProductCachedAttributesFacade;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class DeliveryDateExtension extends AbstractExtension
{
    /**
     * @var \App\Model\Product\ProductCachedAttributesFacade
     */
    private $productCachedAttributesFacade;

    /**
     * @param \App\Model\Product\ProductCachedAttributesFacade $productCachedAttributesFacade
     */
    public function __construct(ProductCachedAttributesFacade $productCachedAttributesFacade)
    {
        $this->productCachedAttributesFacade = $productCachedAttributesFacade;
    }

    /**
     * @return array
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('getDeliveryDate', [$this, 'getDeliveryDate']),
        ];
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param \App\Model\Transport\Transport|null $transport
     * @return null|\DateTime
     */
    public function getDeliveryDate(Product $product, ?Transport $transport = null): ?DateTime
    {
        try {
            $deliveryDate = $this->productCachedAttributesFacade->getExpectedDeliveryDate($product, $transport);
        } catch (NoVisibleTransportsWithoutPickUpPlacesOnDomainException $exception) {
            $deliveryDate = null;
        }

        return $deliveryDate;
    }
}
