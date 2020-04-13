<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Product\Parameter\AdminSelectedParameter;
use App\Model\Product\Parameter\ParameterFacade;
use App\Model\Product\Parameter\ParameterValueInlineEdit;
use Shopsys\FrameworkBundle\Controller\Admin\AdminBaseController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ParameterValueController extends AdminBaseController
{
    /**
     * @var \App\Model\Product\Parameter\AdminSelectedParameter
     */
    private $adminSelectedParameter;

    /**
     * @var \App\Model\Product\Parameter\ParameterValueInlineEdit
     */
    private $parameterValueInlineEdit;

    /**
     * @var \App\Model\Product\Parameter\ParameterFacade
     */
    private $parameterFacade;

    /**
     * @param \App\Model\Product\Parameter\AdminSelectedParameter $adminSelectedParameter
     * @param \App\Model\Product\Parameter\ParameterValueInlineEdit $parameterValueInlineEdit
     * @param \App\Model\Product\Parameter\ParameterFacade $parameterFacade
     */
    public function __construct(
        AdminSelectedParameter $adminSelectedParameter,
        ParameterValueInlineEdit $parameterValueInlineEdit,
        ParameterFacade $parameterFacade
    ) {
        $this->adminSelectedParameter = $adminSelectedParameter;
        $this->parameterValueInlineEdit = $parameterValueInlineEdit;
        $this->parameterFacade = $parameterFacade;
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

        return $this->render('Admin/Content/ParameterValue/list.html.twig', [
            'gridView' => $grid->createView(),
            'parameter' => $this->parameterFacade->getById($parameterId),
        ]);
    }
}
