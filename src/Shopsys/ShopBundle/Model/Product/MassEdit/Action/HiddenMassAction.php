<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\MassEdit\Action;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Shopsys\FrameworkBundle\Model\Product\ProductHiddenRecalculator;
use Shopsys\FrameworkBundle\Model\Product\ProductVisibilityFacade;
use Shopsys\ShopBundle\Model\Product\MassEdit\MassEditActionInterface;
use Shopsys\ShopBundle\Model\Product\Product;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class HiddenMassAction implements MassEditActionInterface
{
    public const NAME = 'hidden';

    private const OPERATION_SHOW = 'show';
    private const OPERATION_HIDE = 'hide';

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\ProductVisibilityFacade
     */
    private $productVisibilityFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\ProductHiddenRecalculator
     */
    private $productHiddenRecalculator;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductVisibilityFacade $productVisibilityFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductHiddenRecalculator $productHiddenRecalculator
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ProductVisibilityFacade $productVisibilityFacade,
        ProductHiddenRecalculator $productHiddenRecalculator
    ) {
        $this->productVisibilityFacade = $productVisibilityFacade;
        $this->productHiddenRecalculator = $productHiddenRecalculator;
        $this->entityManager = $entityManager;
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return t('Skrývání zboží');
    }

    /**
     * @inheritdoc
     */
    public function getOperations(): array
    {
        return [
            self::OPERATION_SHOW => t('Zobrazit'),
            self::OPERATION_HIDE => t('Skrýt'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getValueFormType(string $operation): string
    {
        return HiddenType::class;
    }

    /**
     * @inheritdoc
     */
    public function getValueFormOptions(string $operation): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function perform(QueryBuilder $selectedProductsQueryBuilder, string $operation, $value): void
    {
        $selectedProductsQueryBuilder->select('p.id');
        $productIds = $selectedProductsQueryBuilder->getQuery()->execute();

        $qb = $this->entityManager->createQueryBuilder();

        $qb->update(Product::class, 'p')
            ->set('p.hidden', ':hiddenValue')->setParameter('hiddenValue', $operation === self::OPERATION_HIDE)
            ->set('p.recalculateVisibility', 'TRUE')
            ->where('p.id IN(:productIds)')->setParameter('productIds', $productIds);

        $qb->getQuery()->execute();

        $this->productHiddenRecalculator->calculateHiddenForAll();
        $this->productVisibilityFacade->refreshProductsVisibilityForMarkedDelayed();
    }
}
