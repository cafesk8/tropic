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
    private LuigisBoxObjectFactory $luigisBoxObjectFactory;

    private LuigisBoxClient $luigisBoxClient;

    private ProductFacade $productFacade;

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

        foreach ($luigisBoxExportables as $exportableModel) {
            $luigisCollection = new LuigisBoxObjectCollection();

            if ($exportableModel instanceof Product) {
                try {
                    $luigisBoxObject = $this->luigisBoxObjectFactory->createProduct($exportableModel, $domainConfig);
                } catch (ProductUrlNotLoadedException $exception) {
                    continue;
                }

                $successExportedCallback = function (LuigisBoxExportableInterface $model, DomainConfig $domainConfig) {
                    $this->productFacade->markProductsAsExportedToLuigisBox([$model], $domainConfig->getId());
                };
            } else {
                throw new Exception('Exportable with type ' . gettype($exportableModel) . ' is not mapped for Luigi\'s Box');
            }

            $luigisCollection->add($luigisBoxObject);

            try {
                $this->luigisBoxClient->update($luigisCollection, $domainConfig);
                $successExportedCallback($exportableModel, $domainConfig);
            } catch (LuigisBoxClientException $exception) {
                $exceptionCollection[] = $exception;
            }
        }

        return $exceptionCollection;
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
}