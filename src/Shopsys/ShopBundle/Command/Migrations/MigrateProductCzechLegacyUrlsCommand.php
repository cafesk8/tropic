<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Command\Migrations;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\ShopBundle\Command\Migration\Exception\IncompleteProductUrlLegacySlugDoesNotExistException;
use Shopsys\ShopBundle\Command\Migration\Exception\LegacyPrestaIdDoesNotExistException;
use Shopsys\ShopBundle\Command\Migrations\DataProvider\MigrateProductLCzechLegacyUrlsDataProvider;
use Shopsys\ShopBundle\Component\Domain\DomainHelper;
use Shopsys\ShopBundle\Component\Router\FriendlyUrl\Exception\FriendlyUrlExistsException;
use Shopsys\ShopBundle\Component\Router\FriendlyUrl\FriendlyUrlFacade;
use Shopsys\ShopBundle\Model\Product\ProductFacade;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateProductCzechLegacyUrlsCommand extends Command
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
    private $incompleteLegacySlugsIndexedByEan = [];

    /**
     * @var string[]
     */
    private $legacyPrestaIdsIndexedByEan = [];

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

        $this->incompleteLegacySlugsIndexedByEan = MigrateProductLCzechLegacyUrlsDataProvider::getIncompleteLegacySlugsIndexedByEan();
        $this->legacyPrestaIdsIndexedByEan = MigrateProductLCzechLegacyUrlsDataProvider::getLegacyPrestaIdsIndexedByEan();
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
            $products = $this->productFacade->getMainVariantsWithEan(self::BATCH_LIMIT, $page);
            $productsCount = count($products);
            $page++;

            $this->entityManager->beginTransaction();

            foreach ($products as $product) {
                try {
                    $legacyPrestaId = $this->getLegacyPrestaIdByEan($product->getEan());
                    $incompleteLegacySlug = $this->getIncompleteLegacySlugByEan($product->getEan());

                    $completeLegacySlug = $this->getCompleteLegacySlug($legacyPrestaId, $incompleteLegacySlug);

                    $this->friendlyUrlFacade->addNotMainFriendlyUrl(
                        'front_product_detail',
                        $product->getId(),
                        DomainHelper::CZECH_DOMAIN,
                        $completeLegacySlug
                    );

                    $symfonyStyleIo->success(sprintf('Legacy slug `%s` for product with EAN `%s` has been added', $completeLegacySlug, $product->getEan()));
                } catch (LegacyPrestaIdDoesNotExistException $legacyPrestaIdDoesNotExistException) {
                    $symfonyStyleIo->warning(sprintf('Legacy Presta ID for product with EAN `%s` does not exist', $product->getEan()));
                } catch (IncompleteProductUrlLegacySlugDoesNotExistException $legacySlugDoesNotExistException) {
                    $symfonyStyleIo->warning(sprintf('Legacy incomplete slug for product with EAN `%s` does not exist', $product->getEan()));
                } catch (FriendlyUrlExistsException $friendlyUrlExistsException) {
                    $symfonyStyleIo->warning(sprintf('Legacy complete slug for product with EAN `%s` exists', $product->getEan()));
                }
            }

            $this->entityManager->commit();
            $this->entityManager->clear();
        } while ($productsCount > 0);
    }

    /**
     * @param string $legacyPrestaId
     * @param string $incompleteLegacySlug
     * @return string
     */
    private function getCompleteLegacySlug(string $legacyPrestaId, string $incompleteLegacySlug): string
    {
        return sprintf('%s-%s.html', $legacyPrestaId, $incompleteLegacySlug);
    }

    /**
     * @param string $ean
     * @return string
     */
    private function getIncompleteLegacySlugByEan(string $ean): string
    {
        if (isset($this->incompleteLegacySlugsIndexedByEan[$ean]) === false) {
            throw new IncompleteProductUrlLegacySlugDoesNotExistException();
        }

        return $this->incompleteLegacySlugsIndexedByEan[$ean];
    }

    /**
     * @param string $ean
     * @return string
     */
    private function getLegacyPrestaIdByEan(string $ean): string
    {
        if (isset($this->legacyPrestaIdsIndexedByEan[$ean]) === false) {
            throw new LegacyPrestaIdDoesNotExistException();
        }

        return $this->legacyPrestaIdsIndexedByEan[$ean];
    }
}
