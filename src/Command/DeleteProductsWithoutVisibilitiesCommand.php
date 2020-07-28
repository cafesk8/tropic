<?php

declare(strict_types=1);

namespace App\Command;

use App\Model\Product\ProductFacade;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteProductsWithoutVisibilitiesCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'shopsys:fix:delete-products-without-visibilities';

    private ProductFacade $productFacade;

    private EntityManagerInterface $em;

    /**
     * @param \App\Model\Product\ProductFacade $productFacade
     * @param \Doctrine\ORM\EntityManagerInterface $em
     */
    public function __construct(
        ProductFacade $productFacade,
        EntityManagerInterface $em
    ) {
        parent::__construct();
        $this->productFacade = $productFacade;
        $this->em = $em;
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setDescription('Delete products without product visibilities');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $resultSetMapping = new ResultSetMapping();
        $resultSetMapping->addScalarResult('id', 'productId');

        $queryBuilder = $this->em->createNativeQuery(
            'SELECT P.id
            FROM products P
            LEFT JOIN product_visibilities PV ON PV.product_id = P.id
            WHERE PV.product_id IS NULL',
            $resultSetMapping
        );
        $productsWithoutVisibilities = $queryBuilder->getScalarResult();

        foreach ($productsWithoutVisibilities as $productsWithoutVisibility) {
            $this->productFacade->delete($productsWithoutVisibility['productId']);
        }

        return 0;
    }
}
