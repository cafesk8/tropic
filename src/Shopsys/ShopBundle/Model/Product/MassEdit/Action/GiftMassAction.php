<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\MassEdit\Action;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Shopsys\FrameworkBundle\Form\ProductType;
use Shopsys\ShopBundle\Model\Product\MassEdit\MassEditActionInterface;
use Shopsys\ShopBundle\Model\Product\Product;
use Shopsys\ShopBundle\Model\Product\ProductFacade;

class GiftMassAction implements MassEditActionInterface
{
    private const NAME = 'gifts';

    private const OPERATION_ADD = 'add';
    private const OPERATION_REMOVE = 'remove';

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * GiftMassAction constructor.
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @param \Shopsys\ShopBundle\Model\Product\ProductFacade $productFacade
     */
    public function __construct(EntityManagerInterface $entityManager, ProductFacade $productFacade)
    {
        $this->entityManager = $entityManager;
        $this->productFacade = $productFacade;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return t('Dárek');
    }

    /**
     * @return string[]
     */
    public function getOperations(): array
    {
        return [
            self::OPERATION_ADD => t('Přidat'),
            self::OPERATION_REMOVE => t('Odebrat'),
        ];
    }

    /**
     * @param string $operation Symbolic operation name defined in getOperations() array indices
     * @return string|\Symfony\Component\Form\FormTypeInterface|\Symfony\Component\Form\FormTypeInterface[]
     */
    public function getValueFormType(string $operation)
    {
        return ProductType::class;
    }

    /**
     * @param string $operation Symbolic operation name defined in getOperations() array indices
     * @return array
     */
    public function getValueFormOptions(string $operation): array
    {
        return [
            'attr' => [
                'containerClass' => 'js-hide-when-operation-is-remove',
            ],
        ];
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder $selectedProductsQueryBuilder
     * @param string $operation Symbolic operation name defined in getOperations() array indices
     * @param mixed $value Single value or array of values (array key = input name)
     */
    public function perform(QueryBuilder $selectedProductsQueryBuilder, string $operation, $value): void
    {
        $selectedProductsQueryBuilder->select('p.id');
        $productIds = $selectedProductsQueryBuilder->getQuery()->execute();

        $qb = $this->entityManager->createQueryBuilder();

        $qb->update(Product::class, 'p')
            ->set('p.gift', ':gift')->setParameter('gift', $this->getGift($operation, $value))
            ->where('p.id IN(:productIds)')->setParameter('productIds', $productIds);

        $qb->getQuery()->execute();
    }

    /**
     * @param string $operation
     * @param mixed $value
     * @return \Shopsys\ShopBundle\Model\Product\Product|null
     */
    private function getGift(string $operation, $value): ?Product
    {
        if ($operation === self::OPERATION_REMOVE) {
            return null;
        }

        return $this->productFacade->getById($value);
    }
}
