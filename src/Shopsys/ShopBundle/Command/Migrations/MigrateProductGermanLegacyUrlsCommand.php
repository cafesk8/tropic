<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Command\Migrations;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\ShopBundle\Command\Migration\Exception\CompleteProductUrlLegacySlugDoesNotExistException;
use Shopsys\ShopBundle\Command\Migrations\DataProvider\MigrateProductGermanLegacyUrlsDataProvider;
use Shopsys\ShopBundle\Component\Domain\DomainHelper;
use Shopsys\ShopBundle\Component\Router\FriendlyUrl\Exception\FriendlyUrlExistsException;
use Shopsys\ShopBundle\Component\Router\FriendlyUrl\FriendlyUrlFacade;
use Shopsys\ShopBundle\Model\Product\ProductFacade;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateProductGermanLegacyUrlsCommand extends Command
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
    private $completeLegacySlugsIndexedByEan = [];

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

        $this->completeLegacySlugsIndexedByEan = MigrateProductGermanLegacyUrlsDataProvider::getCompleteLegacySlugsIndexedByEan();
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
                    $completeLegacySlug = $this->getCompleteLegacySlugByEan($product->getEan());

                    $this->friendlyUrlFacade->addNotMainFriendlyUrl(
                        'front_product_detail',
                        $product->getId(),
                        DomainHelper::GERMAN_DOMAIN,
                        $completeLegacySlug
                    );

                    $symfonyStyleIo->success(sprintf('Legacy slug `%s` for product with EAN `%s` has been added', $completeLegacySlug, $product->getEan()));
                } catch (CompleteProductUrlLegacySlugDoesNotExistException $legacySlugDoesNotExistException) {
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
     * @param string $ean
     * @return string
     */
    private function getCompleteLegacySlugByEan(string $ean): string
    {
        if (isset($this->completeLegacySlugsIndexedByEan[$ean]) === false) {
            throw new CompleteProductUrlLegacySlugDoesNotExistException();
        }

        return $this->completeLegacySlugsIndexedByEan[$ean];
    }
}
