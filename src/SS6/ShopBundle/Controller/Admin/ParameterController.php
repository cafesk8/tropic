<?php

namespace SS6\ShopBundle\Controller\Admin;

use Doctrine\ORM\EntityManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use SS6\ShopBundle\Component\Controller\AdminBaseController;
use SS6\ShopBundle\Component\Router\Security\Annotation\CsrfProtection;
use SS6\ShopBundle\Model\Product\Parameter\ParameterFacade;
use SS6\ShopBundle\Model\Product\Parameter\ParameterInlineEdit;

class ParameterController extends AdminBaseController {

	/**
	 * @var \Doctrine\ORM\EntityManager
	 */
	private $em;

	/**
	 * @var \SS6\ShopBundle\Model\Product\Parameter\ParameterFacade
	 */
	private $parameterFacade;

	/**
	 * @var \SS6\ShopBundle\Model\Product\Parameter\ParameterInlineEdit
	 */
	private $parameterInlineEdit;

	public function __construct(
		EntityManager $em,
		ParameterFacade $parameterFacade,
		ParameterInlineEdit $parameterInlineEdit
	) {
		$this->em = $em;
		$this->parameterFacade = $parameterFacade;
		$this->parameterInlineEdit = $parameterInlineEdit;
	}

	/**
	 * @Route("/product/parameter/list/")
	 */
	public function listAction() {
		$grid = $this->parameterInlineEdit->getGrid();

		return $this->render('@SS6Shop/Admin/Content/Parameter/list.html.twig', [
			'gridView' => $grid->createView(),
		]);
	}

	/**
	 * @Route("/product/parameter/delete/{id}", requirements={"id" = "\d+"})
	 * @CsrfProtection
	 * @param int $id
	 */
	public function deleteAction($id) {
		try {
			$fullName = $this->parameterFacade->getById($id)->getName();
			$this->em->transactional(
				function () use ($id) {
					$this->parameterFacade->deleteById($id);
				}
			);

			$this->getFlashMessageSender()->addSuccessFlashTwig('Parametr <strong>{{ name }}</strong> byl smazán', [
				'name' => $fullName,
			]);
		} catch (\SS6\ShopBundle\Model\Product\Parameter\Exception\ParameterNotFoundException $ex) {
			$this->getFlashMessageSender()->addErrorFlash('Zvolený parametr neexistuje.');
		}

		return $this->redirect($this->generateUrl('admin_parameter_list'));
	}

}
