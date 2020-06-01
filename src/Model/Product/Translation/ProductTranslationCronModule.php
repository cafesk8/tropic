<?php

declare(strict_types=1);

namespace App\Model\Product\Translation;

use App\Environment;
use App\Model\Product\ProductFacade;
use Exception;
use Shopsys\FrameworkBundle\Model\Product\Elasticsearch\ProductExportSubscriber;
use Shopsys\Plugin\Cron\SimpleCronModuleInterface;
use Symfony\Bridge\Monolog\Logger;

class ProductTranslationCronModule implements SimpleCronModuleInterface
{
    /**
     * @var \Symfony\Bridge\Monolog\Logger
     */
    private $logger;

    /**
     * @var \App\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @var \App\Model\Product\Translation\ProductTranslationFacade
     */
    private $productTranslationFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Elasticsearch\ProductExportSubscriber
     */
    private $productExportSubscriber;

    /**
     * @param \App\Model\Product\ProductFacade $productFacade
     * @param \App\Model\Product\Translation\ProductTranslationFacade $productTranslationFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\Elasticsearch\ProductExportSubscriber $productExportSubscriber
     */
    public function __construct(ProductFacade $productFacade, ProductTranslationFacade $productTranslationFacade, ProductExportSubscriber $productExportSubscriber)
    {
        $this->productFacade = $productFacade;
        $this->productTranslationFacade = $productTranslationFacade;
        $this->productExportSubscriber = $productExportSubscriber;
    }

    /**
     * @inheritDoc
     */
    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function run()
    {
        if (Environment::getEnvironment(true) === 'dev') {
            echo 'Překlad přes Google Translate API je zpoplatněn! Pokud jste jej zapnuli omylem, urychleně jej ukončete.';

            for ($i = 5; $i > 0; $i--) {
                echo PHP_EOL . $i;
                sleep(1);
            }
        }

        $productDomains = $this->productFacade->getProductDomainsForDescriptionTranslation();

        foreach ($productDomains as $productDomain) {
            try {
                $this->productTranslationFacade->translateDescription($productDomain);
            } catch (Exception $exception) {
                $this->logger->addError('Popis produktu nebyl přeložen!', [
                    'message' => $exception->getMessage(),
                    'product' => $productDomain->getProduct(),
                ]);
            }
        }

        $productDomains = $this->productFacade->getProductDomainsForShortDescriptionTranslation();

        foreach ($productDomains as $productDomain) {
            try {
                $this->productTranslationFacade->translateShortDescription($productDomain);
            } catch (Exception $exception) {
                $this->logger->addError('Krátký popis produktu nebyl přeložen!', [
                    'message' => $exception->getMessage(),
                    'product' => $productDomain->getProduct(),
                ]);
            }
        }

        $this->productExportSubscriber->exportScheduledRows();
    }
}
