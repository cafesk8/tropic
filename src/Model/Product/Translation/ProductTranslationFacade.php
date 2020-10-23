<?php

declare(strict_types=1);

namespace App\Model\Product\Translation;

use App\Component\Domain\DomainHelper;
use App\Model\Product\ProductDomain;
use App\Model\Product\ProductFacade;
use Google\Cloud\Translate\V2\TranslateClient;

class ProductTranslationFacade
{
    private TranslateClient $translateClient;

    private ProductFacade $productFacade;

    private bool $translationEnabled;

    /**
     * @param \Google\Cloud\Translate\V2\TranslateClient $translateClient
     * @param \App\Model\Product\ProductFacade $productFacade
     * @param bool $translationEnabled
     */
    public function __construct(TranslateClient $translateClient, ProductFacade $productFacade, bool $translationEnabled)
    {
        $this->translateClient = $translateClient;
        $this->productFacade = $productFacade;
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
            $this->productFacade->setDescriptionTranslation(
                $productDomain->getProduct()->getId(),
                DomainHelper::SLOVAK_DOMAIN,
                $translation['text'],
                md5($productDomain->getDescription()),
                false
            );
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
            $this->productFacade->setDescriptionTranslation(
                $productDomain->getProduct()->getId(),
                DomainHelper::SLOVAK_DOMAIN,
                $translation['text'],
                md5($productDomain->getShortDescription()),
                true
            );
        }
    }
}
