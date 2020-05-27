<?php

declare(strict_types=1);

namespace App\Model\Product\Translation;

use App\Component\Domain\DomainHelper;
use App\Model\Product\ProductDataFactory;
use App\Model\Product\ProductDomain;
use App\Model\Product\ProductFacade;
use Google\Cloud\Translate\V2\TranslateClient;

class ProductTranslationFacade
{
    /**
     * @var \Google\Cloud\Translate\V2\TranslateClient
     */
    private $translateClient;

    /**
     * @var \App\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @var \App\Model\Product\ProductDataFactory
     */
    private $productDataFactory;

    /**
     * @var bool
     */
    private $translationEnabled;

    /**
     * @param \Google\Cloud\Translate\V2\TranslateClient $translateClient
     * @param \App\Model\Product\ProductFacade $productFacade
     * @param \App\Model\Product\ProductDataFactory $productDataFactory
     * @param bool $translationEnabled
     */
    public function __construct(TranslateClient $translateClient, ProductFacade $productFacade, ProductDataFactory $productDataFactory, bool $translationEnabled)
    {
        $this->translateClient = $translateClient;
        $this->productFacade = $productFacade;
        $this->productDataFactory = $productDataFactory;
        $this->translationEnabled = $translationEnabled;
    }

    /**
     * @param \App\Model\Product\ProductDomain $productDomain
     */
    public function translateDescription(ProductDomain $productDomain): void
    {
        if ($this->translationEnabled) {
            $translation = $this->translateClient->translate($productDomain->getDescription(), [
                'source' => DomainHelper::CZECH_LOCALE,
                'target' => DomainHelper::SLOVAK_LOCALE,
            ]);
            $productData = $this->productDataFactory->createFromProduct($productDomain->getProduct());
            $productData->descriptions[DomainHelper::SLOVAK_DOMAIN] = $translation['text'];
            $productData->descriptionHashes[DomainHelper::CZECH_DOMAIN] = md5($productDomain->getDescription());
            $this->productFacade->edit($productDomain->getProduct()->getId(), $productData);
        }
    }

    /**
     * @param \App\Model\Product\ProductDomain $productDomain
     */
    public function translateShortDescription(ProductDomain $productDomain): void
    {
        if ($this->translationEnabled) {
            $translation = $this->translateClient->translate($productDomain->getShortDescription(), [
                'source' => DomainHelper::CZECH_LOCALE,
                'target' => DomainHelper::SLOVAK_LOCALE,
            ]);
            $productData = $this->productDataFactory->createFromProduct($productDomain->getProduct());
            $productData->shortDescriptions[DomainHelper::SLOVAK_DOMAIN] = $translation['text'];
            $productData->shortDescriptionHashes[DomainHelper::CZECH_DOMAIN] = md5($productDomain->getShortDescription());
            $this->productFacade->edit($productDomain->getProduct()->getId(), $productData);
        }
    }
}
