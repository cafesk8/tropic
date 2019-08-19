<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Admin;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Shopsys\FrameworkBundle\Controller\Admin\AdminBaseController;
use Shopsys\ShopBundle\Model\Product\Parameter\AdminSelectedParameter;
use Shopsys\ShopBundle\Model\Product\Parameter\ParameterValueInlineEdit;
use Symfony\Component\HttpFoundation\Response;

class ParameterValueController extends AdminBaseController
{
    /**
     * @var \Shopsys\ShopBundle\Model\Product\Parameter\AdminSelectedParameter
     */
    private $adminSelectedParameter;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\Parameter\ParameterValueInlineEdit
     */
    private $parameterValueInlineEdit;

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Parameter\AdminSelectedParameter $adminSelectedParameter
     * @param \Shopsys\ShopBundle\Model\Product\Parameter\ParameterValueInlineEdit $parameterValueInlineEdit
     */
    public function __construct(
        AdminSelectedParameter $adminSelectedParameter,
        ParameterValueInlineEdit $parameterValueInlineEdit
    ) {
        $this->adminSelectedParameter = $adminSelectedParameter;
        $this->parameterValueInlineEdit = $parameterValueInlineEdit;
    }

    /**
     * @Route("/product/parameter-value/list/{parameterId}", requirements={"id" = "\d+"})
     * @param int $parameterId
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction(int $parameterId): Response
    {
        $this->adminSelectedParameter->setSelectParameter($parameterId);

        $grid = $this->parameterValueInlineEdit->getGrid();

        return $this->render('@ShopsysShop/Admin/Content/ParameterValue/list.html.twig', [
            'gridView' => $grid->createView(),
        ]);
    }
}
