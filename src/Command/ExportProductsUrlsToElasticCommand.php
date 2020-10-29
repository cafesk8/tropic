<?php

declare(strict_types=1);

namespace App\Command;

use App\Component\Elasticsearch\IndexFacade;
use App\Model\Product\Elasticsearch\ProductExportRepository;
use App\Model\Product\Elasticsearch\ProductIndex;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Elasticsearch\IndexDefinitionLoader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportProductsUrlsToElasticCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'shopsys:elasticsearch:urls-export';

    private Domain $domain;

    private IndexDefinitionLoader $indexDefinitionLoader;

    private ProductIndex $productIndex;

    private IndexFacade $indexFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Component\Elasticsearch\IndexDefinitionLoader $indexDefinitionLoader
     * @param \App\Model\Product\Elasticsearch\ProductIndex $productIndex
     * @param \App\Component\Elasticsearch\IndexFacade $indexFacade
     */
    public function __construct(
        Domain $domain,
        IndexDefinitionLoader $indexDefinitionLoader,
        ProductIndex $productIndex,
        IndexFacade $indexFacade
    ) {
        parent::__construct();
        $this->domain = $domain;
        $this->indexDefinitionLoader = $indexDefinitionLoader;
        $this->productIndex = $productIndex;
        $this->indexFacade = $indexFacade;
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setDescription('Export products URLs to Elasticsearch');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->domain->getAllIds() as $domainId) {
            $indexDefinition = $this->indexDefinitionLoader->getIndexDefinition($this->productIndex::getName(), $domainId);
            $this->indexFacade->export($this->productIndex, $indexDefinition, $output, ProductExportRepository::SCOPE_URLS);
        }

        return 0;
    }
}
