<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\MassEdit;

use Doctrine\ORM\QueryBuilder;
use Shopsys\ShopBundle\Model\Product\MassEdit\Action\HiddenMassAction;
use Shopsys\ShopBundle\Model\Product\ProductRepository;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class MassEditFacade
{
    public const MASS_EDIT_MAX_LIMIT = 1000;
    public const SELECT_TYPE_CHECKED = 'selectTypeChecked';
    public const SELECT_TYPE_ALL_RESULTS = 'selectTypeAllResults';

    private const MASS_EDIT_FORM_NAME = 'mass_edit_form';

    /**
     * @var \Shopsys\ShopBundle\Model\Product\MassEdit\MassEditFormFactory
     */
    private $massEditFormFactory;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductRepository
     */
    private $productRepository;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\MassEdit\MassEditActionProvider
     */
    private $massEditActionProvider;

    /**
     * MassEditFacade constructor.
     * @param \Shopsys\ShopBundle\Model\Product\MassEdit\MassEditFormFactory $massEditFormFactory
     * @param \Shopsys\ShopBundle\Model\Product\ProductRepository $productRepository
     * @param \Shopsys\ShopBundle\Model\Product\MassEdit\MassEditActionProvider $massEditActionProvider
     */
    public function __construct(MassEditFormFactory $massEditFormFactory, ProductRepository $productRepository, MassEditActionProvider $massEditActionProvider)
    {
        $this->massEditFormFactory = $massEditFormFactory;
        $this->productRepository = $productRepository;
        $this->massEditActionProvider = $massEditActionProvider;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\Form\FormInterface
     */
    public function createMassEditFormFromRequest(Request $request): FormInterface
    {
        $massEditData = $request->get(self::MASS_EDIT_FORM_NAME);
        $massEditFormData = $this->getActionFormViewDataByRequestData($massEditData);

        return $this->massEditFormFactory->createMassEditForm(self::MASS_EDIT_FORM_NAME, $massEditFormData);
    }

    /**
     * @param array|null $requestData
     * @return array
     */
    public function getActionFormViewDataByRequestData(?array $requestData = null): array
    {
        if ($requestData === null || count($requestData) === 0) {
            return $this->createDefaultRuleFormViewData(HiddenMassAction::NAME, null);
        }

        return $requestData;
    }

    /**
     * @param string $actionName
     * @param string|null $operationName
     * @return array
     */
    public function createDefaultRuleFormViewData(string $actionName, ?string $operationName): array
    {
        return [
            'selectType' => self::SELECT_TYPE_CHECKED,
            'subject' => $actionName,
            'operation' => $operationName,
            'value' => null,
        ];
    }

    /**
     * @param string $selectedSubjectName
     * @param string $selectedOperationName
     * @return \Symfony\Component\Form\FormInterface
     */
    public function createMassEditForm(string $selectedSubjectName, ?string $selectedOperationName): FormInterface
    {
        $massEditFormData = $this->createDefaultRuleFormViewData($selectedSubjectName, $selectedOperationName);

        return $this->massEditFormFactory->createMassEditForm(self::MASS_EDIT_FORM_NAME, $massEditFormData);
    }

    /**
     * @param array $formData
     * @param \Doctrine\ORM\QueryBuilder $selectQueryBuilder
     * @param int[] $checkedProductIds
     *
     * @return int
     */
    public function getCountOfSelectedProducts(
        array $formData,
        QueryBuilder $selectQueryBuilder,
        array $checkedProductIds
    ): int {
        $selectedProductsQueryBuilder = $this->getSelectedProductQueryBuilder(
            $formData['selectType'],
            $selectQueryBuilder,
            $checkedProductIds
        );

        return $selectedProductsQueryBuilder->select('COUNT(p)')->getQuery()->getSingleScalarResult();
    }

    /**
     * @param string $selectType
     * @param \Doctrine\ORM\QueryBuilder $selectQueryBuilder
     * @param int[] $checkedProductIds
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function getSelectedProductQueryBuilder(
        string $selectType,
        QueryBuilder $selectQueryBuilder,
        array $checkedProductIds
    ): QueryBuilder {
        if ($selectType === self::SELECT_TYPE_CHECKED) {
            $selectedProductsQueryBuilder = $this->productRepository->getProductQueryBuilder()
                ->where('p.id IN (:productIds)')->setParameter('productIds', $checkedProductIds);
        } elseif ($selectType === self::SELECT_TYPE_ALL_RESULTS) {
            $selectedProductsQueryBuilder = (clone $selectQueryBuilder);
        } else {
            throw new \Shopsys\ShopBundle\Model\Product\MassEdit\Exception\UnknownMassEditSelectionTypeException($selectType);
        }

        return $selectedProductsQueryBuilder;
    }

    /**
     * @param array $formData
     * @param \Doctrine\ORM\QueryBuilder $selectQueryBuilder
     * @param int[] $checkedProductIds
     */
    public function performMassEdit(
        array $formData,
        QueryBuilder $selectQueryBuilder,
        array $checkedProductIds
    ): void {
        $subject = $formData['subject'];
        $operation = $formData['operation'];
        $selectType = $formData['selectType'];

        unset($formData['subject'], $formData['operation'], $formData['selectType']);

        $value = count($formData) === 1 ? array_values($formData)[0] : $formData;

        $action = $this->massEditActionProvider->getAction($subject);

        $action->perform(
            $this->getSelectedProductQueryBuilder(
                $selectType,
                $selectQueryBuilder,
                $checkedProductIds
            ),
            $operation,
            $value
        );
    }
}
