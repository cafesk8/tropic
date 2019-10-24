<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Command\Migrations;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\ShopBundle\Command\Migration\Exception\ProductUrlLegacySlugDoesNotExistException;
use Shopsys\ShopBundle\Command\Migrations\DataProvider\MigrateProductSlovakLegacyUrlsDataProvider;
use Shopsys\ShopBundle\Component\Domain\DomainHelper;
use Shopsys\ShopBundle\Component\Router\FriendlyUrl\Exception\FriendlyUrlExistsException;
use Shopsys\ShopBundle\Component\Router\FriendlyUrl\FriendlyUrlFacade;
use Shopsys\ShopBundle\Model\Product\ProductFacade;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateProductSlovakLegacyUrlsCommand extends Command
{
    private const BATCH_LIMIT = 10;

    /**
     * @var string
     */
    protected static $defaultName = 'shopsys:migrate:product-legacy-urls';

    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @var string[]
     */
    private $legacySlugsIndexedByCatnum = [];

    /**
     * @var \Shopsys\ShopBundle\Component\Router\FriendlyUrl\FriendlyUrlFacade
     */
    private $friendlyUrlFacade;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @param \Shopsys\ShopBundle\Model\Product\ProductFacade $productFacade
     * @param \Shopsys\ShopBundle\Component\Router\FriendlyUrl\FriendlyUrlFacade $friendlyUrlFacade
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ProductFacade $productFacade,
        FriendlyUrlFacade $friendlyUrlFacade
    ) {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->productFacade = $productFacade;
        $this->friendlyUrlFacade = $friendlyUrlFacade;

        $this->legacySlugsIndexedByCatnum = MigrateProductSlovakLegacyUrlsDataProvider::getLegacySlugsIndexedByCatnum();
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Migrate product legacy URLs');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $symfonyStyleIo = new SymfonyStyle($input, $output);

        $page = 0;
        do {
            $products = $this->productFacade->getMainVariantsWithCatnum(self::BATCH_LIMIT, $page);
            $productsCount = count($products);
            $page++;

            $this->entityManager->beginTransaction();

            foreach ($products as $product) {
                try {
                    $legacySlugs = $this->getLegacySlugs($product->getCatnum());

                    foreach ($legacySlugs as $legacySlug) {
                        $this->friendlyUrlFacade->addNotMainFriendlyUrl(
                            'front_product_detail',
                            $product->getId(),
                            DomainHelper::SLOVAK_DOMAIN,
                            $legacySlug
                        );
                        $symfonyStyleIo->success(sprintf('Legacy slug `%s` for product with catnum `%s` has been added', $legacySlug, $product->getCatnum()));
                    }

                } catch (ProductUrlLegacySlugDoesNotExistException $legacySlugDoesNotExistException) {
                    $symfonyStyleIo->warning(sprintf('Legacy slug for product with catnum `%s` does not exist in the provided data source', $product->getCatnum()));
                } catch (FriendlyUrlExistsException $friendlyUrlExistsException) {
                    $symfonyStyleIo->warning(sprintf('Legacy slug for product with catnum `%s` already exists', $product->getCatnum()));
                }
            }

            $this->entityManager->commit();
            $this->entityManager->clear();
        } while ($productsCount > 0);
    }

    /**
     * @param string $productCatnum
     * @return string[]
     */
    private function getLegacySlugs(string $productCatnum): array
    {
        if (!isset($this->legacySlugsIndexedByCatnum[$productCatnum]) || empty($this->legacySlugsIndexedByCatnum[$productCatnum])) {
            throw new ProductUrlLegacySlugDoesNotExistException();
        }

        return $this->legacySlugsIndexedByCatnum[$productCatnum];
    }
}
