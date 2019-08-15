<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Mall;

use Exception;
use MPAPI\Entity\Products\Product;
use MPAPI\Exceptions\ApplicationException;
use MPAPI\Exceptions\ForceTokenException;
use MPAPI\Services\Products;
use Symfony\Bridge\Monolog\Logger;

class MallFacade
{
    /**
     * @var \Shopsys\ShopBundle\Component\Mall\MallClient
     */
    private $mallClient;

    /**
     * @var \Symfony\Bridge\Monolog\Logger
     */
    private $logger;

    /**
     * @param \Shopsys\ShopBundle\Component\Mall\MallClient $mallClient
     * @param \Symfony\Bridge\Monolog\Logger $logger
     */
    public function __construct(MallClient $mallClient, Logger $logger)
    {
        $this->mallClient = $mallClient;
        $this->logger = $logger;
    }

    /**
     * @param \MPAPI\Entity\Products\Product $product
     * @return bool
     */
    public function createOrUpdateProduct(Product $product): bool
    {
        $products = new Products($this->mallClient->getClient());

        try {
            try {
                $products->put($product->getId(), $product);
            } catch (ForceTokenException $forceTokenException) {
                $forceToken = $forceTokenException->getForceToken();
                $products->put($product->getId(), $product, null, $forceToken);
            } catch (ApplicationException $exception) {
                $products->post($product);
            }
        } catch (Exception $exception) {
            $this->logger->addError(sprintf('Create or update product to Mall.cz failed due to: %s', $exception->getMessage()), [
                'exception' => $exception,
            ]);
            return false;
        }

        return true;
    }
}
