<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\MassEdit\Action;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Internal\Hydration\IterableResult;
use Doctrine\ORM\QueryBuilder;
use Shopsys\FrameworkBundle\Model\Product\Flag\FlagFacade;
use Shopsys\ShopBundle\Model\Product\MassEdit\MassEditActionInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class FlagsMassAction implements MassEditActionInterface
{
    private const NAME = 'flags';

    private const OPERATION_ADD = 'add';
    private const OPERATION_REMOVE = 'remove';
    private const OPERATION_SET = 'set';

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Flag\FlagFacade
     */
    private $flagFacade;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Flag\FlagFacade $flagFacade
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     */
    public function __construct(
        FlagFacade $flagFacade,
        EntityManagerInterface $entityManager
    ) {
        $this->flagFacade = $flagFacade;
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
        return t('Příznak');
    }

    /**
     * @inheritdoc
     */
    public function getOperations(): array
    {
        return [
            self::OPERATION_ADD => t('Přidat'),
            self::OPERATION_REMOVE => t('Odebrat'),
            self::OPERATION_SET => t('Nastavit'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getValueFormType(string $operation): string
    {
        return ChoiceType::class;
    }

    /**
     * @inheritdoc
     */
    public function getValueFormOptions(string $operation): array
    {
        $flags = $this->flagFacade->getAll();
        $defaultFlagForFreeTransportAndPayment = $this->flagFacade->getDefaultFlagForFreeTransportAndPayment();
        if (in_array($defaultFlagForFreeTransportAndPayment, $flags, true)) {
            $key = array_search($defaultFlagForFreeTransportAndPayment, $flags, true);

            unset($flags[$key]);
        }

        return [
            'required' => true,
            'choices' => $flags,
            'choice_label' => 'name',
            'choice_value' => 'id',
        ];
    }

    /**
     * @inheritdoc
     */
    public function perform(QueryBuilder $selectedProductsQueryBuilder, string $operation, $value): void
    {
        $productsIterableResult = $selectedProductsQueryBuilder
            ->select('p')
            ->distinct()
            ->getQuery()->iterate();

        switch ($operation) {
            case self::OPERATION_ADD:
                $this->performOperationAdd($productsIterableResult, $value);
                break;
            case self::OPERATION_REMOVE:
                $this->performOperationRemove($productsIterableResult, $value);
                break;
            case self::OPERATION_SET:
                $this->performOperationSet($productsIterableResult, $value);
                break;
        }
    }

    /**
     * @param \Doctrine\ORM\Internal\Hydration\IterableResult $productsIterableResult
     * @param mixed $value
     */
    private function performOperationAdd(IterableResult $productsIterableResult, $value): void
    {
        foreach ($productsIterableResult as $row) {
            /** @var \Shopsys\ShopBundle\Model\Product\Product $product */
            $product = $row[0];
            $flags = $product->getFlags()->toArray();
            if (!in_array($value, $flags, true)) {
                $flags[] = $value;
                $product->editFlags($flags);
                $this->entityManager->flush($product);
            }
        }
    }

    /**
     * @param \Doctrine\ORM\Internal\Hydration\IterableResult $productsIterableResult
     * @param mixed $value
     */
    private function performOperationRemove(IterableResult $productsIterableResult, $value): void
    {
        foreach ($productsIterableResult as $row) {
            /** @var \Shopsys\ShopBundle\Model\Product\Product $product */
            $product = $row[0];
            $flags = $product->getFlags()->toArray();
            $key = array_search($value, $flags, true);
            if ($key !== false) {
                unset($flags[$key]);
                $product->editFlags($flags);
                $this->entityManager->flush($product);
            }
        }
    }

    /**
     * @param \Doctrine\ORM\Internal\Hydration\IterableResult $productsIterableResult
     * @param mixed $value
     */
    private function performOperationSet(IterableResult $productsIterableResult, $value): void
    {
        foreach ($productsIterableResult as $row) {
            /** @var \Shopsys\ShopBundle\Model\Product\Product $product */
            $product = $row[0];
            $flags = [$value];
            $product->editFlags($flags);
            $this->entityManager->flush($product);
        }
    }
}
