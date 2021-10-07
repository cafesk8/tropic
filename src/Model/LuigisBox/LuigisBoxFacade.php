<?php

declare(strict_types=1);

namespace App\Model\LuigisBox;

use App\Component\LuigisBox\LuigisBoxClient;
use App\Component\LuigisBox\LuigisBoxClientException;
use App\Model\Product\Product;
use App\Model\Product\ProductFacade;
use Exception;
use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;
use Shopsys\FrameworkBundle\Model\Product\Collection\Exception\ProductUrlNotLoadedException;

class LuigisBoxFacade
{
    private const UPDATE_BATCH_SIZE = 5;

    private LuigisBoxObjectFactory $luigisBoxObjectFactory;

    private LuigisBoxClient $luigisBoxClient;

    private ProductFacade $productFacade;

    private array $callbacks;

    /**
     * @param \App\Model\LuigisBox\LuigisBoxObjectFactory $luigisBoxObjectFactory
     * @param \App\Component\LuigisBox\LuigisBoxClient $luigisBoxClient
     * @param \App\Model\Product\ProductFacade $productFacade
     */
    public function __construct(
        LuigisBoxObjectFactory $luigisBoxObjectFactory,
        LuigisBoxClient $luigisBoxClient,
        ProductFacade $productFacade
    ) {
        $this->luigisBoxObjectFactory = $luigisBoxObjectFactory;
        $this->luigisBoxClient = $luigisBoxClient;
        $this->productFacade = $productFacade;
    }

    /**
     * @param \App\Model\LuigisBox\LuigisBoxExportableInterface[] $luigisBoxExportables
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @return \App\Component\LuigisBox\LuigisBoxClientException[]
     */
    public function sendToApi(array $luigisBoxExportables, DomainConfig $domainConfig): array
    {
        $exceptionCollection = [];
        $luigisCollection = new LuigisBoxObjectCollection();

        foreach ($luigisBoxExportables as $exportableModel) {
            if ($exportableModel instanceof Product) {
                try {
                    $luigisBoxObject = $this->luigisBoxObjectFactory->createProduct($exportableModel, $domainConfig);
                } catch (ProductUrlNotLoadedException $exception) {
                    continue;
                }

                $this->collectCallbackObject(Product::class, $exportableModel);
            } else {
                throw new Exception('Exportable with type ' . gettype($exportableModel) . ' is not mapped for Luigi\'s Box');
            }

            $luigisCollection->add($luigisBoxObject);

            if ($luigisCollection->count() > self::UPDATE_BATCH_SIZE) {
                $exceptionCollection[] = $this->updateObjects($luigisCollection, $domainConfig);
            }
        }

        $exceptionCollection[] = $this->updateObjects($luigisCollection, $domainConfig);

        return array_filter($exceptionCollection);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @return \App\Model\LuigisBox\LuigisBoxExportableInterface[]
     */
    public function getExportableObjects(DomainConfig $domainConfig): array
    {
        $objects = $this->productFacade->getForLuigisBoxExport($domainConfig->getId());
        $this->luigisBoxObjectFactory->loadUrls($objects, $domainConfig);

        return $objects;
    }

    /**
     * @param \App\Model\LuigisBox\LuigisBoxObjectCollection $collection
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @return \App\Component\LuigisBox\LuigisBoxClientException|null
     */
    private function updateObjects(LuigisBoxObjectCollection $collection, DomainConfig $domainConfig): ?LuigisBoxClientException
    {
        if ($collection->count() === 0) {
            return null;
        }

        $caughtException = null;

        try {
            $this->luigisBoxClient->update($collection, $domainConfig);
            $this->callCallbacks($domainConfig);
            $this->removeOldObjects($collection, $domainConfig);
        } catch (LuigisBoxClientException $exception) {
            $caughtException = $exception;
        } finally {
            $collection->clear();
        }

        return $caughtException;
    }

    /**
     * @param string $type
     * @param \App\Model\LuigisBox\LuigisBoxExportableInterface $object
     */
    private function collectCallbackObject(string $type, LuigisBoxExportableInterface $object): void
    {
        if (!isset($this->callbacks[$type])) {
            if ($type === Product::class) {
                $this->callbacks[$type]['method'] = function (array $models, DomainConfig $domainConfig) {
                    $this->productFacade->markProductsAsExportedToLuigisBox($models, $domainConfig->getId());
                };
            }
        }

        $this->callbacks[$type]['objects'][] = $object;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     */
    private function callCallbacks(DomainConfig $domainConfig): void
    {
        foreach ($this->callbacks as &$callback) {
            $callback['method']($callback['objects'], $domainConfig);
            unset($callback['objects']);
        }
    }

    /**
     * Temporary function to migrate from URL identification to catnum/ID identification
     *
     * @param \App\Model\LuigisBox\LuigisBoxObjectCollection $collection
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     */
    private function removeOldObjects(LuigisBoxObjectCollection $collection, DomainConfig $domainConfig): void
    {
        $collection->convertToOldIdentification();
        $this->luigisBoxClient->remove($collection, $domainConfig);
    }
}