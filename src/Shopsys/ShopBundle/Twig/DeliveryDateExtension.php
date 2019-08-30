<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Twig;

use DateTime;
use Shopsys\FrameworkBundle\Model\Product\ProductCachedAttributesFacade;
use Shopsys\ShopBundle\Model\Transport\DeliveryDate\Exception\NoVisibleTransportsWithoutPickUpPlacesOnDomainException;
use Shopsys\ShopBundle\Model\Transport\Transport;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class DeliveryDateExtension extends AbstractExtension
{
    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductCachedAttributesFacade
     */
    private $productCachedAttributesFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductCachedAttributesFacade $productCachedAttributesFacade
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
     * @param \Shopsys\ShopBundle\Model\Transport\Transport|null $transport
     * @return null|\DateTime
     */
    public function getDeliveryDate(?Transport $transport = null): ?DateTime
    {
        try {
            $deliveryDate = $this->productCachedAttributesFacade->getExpectedDeliveryDate($transport);
        } catch (NoVisibleTransportsWithoutPickUpPlacesOnDomainException $exception) {
            $deliveryDate = null;
        }
        return $deliveryDate;
    }
}
