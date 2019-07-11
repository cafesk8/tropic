<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Admin;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Shopsys\FrameworkBundle\Component\Router\Security\Annotation\CsrfProtection;
use Shopsys\FrameworkBundle\Controller\Admin\ParameterController as BaseParameterController;
use Shopsys\FrameworkBundle\Model\Product\Parameter\Exception\ParameterNotFoundException;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\ShopBundle\Model\Product\Parameter\Exception\ParameterUsedAsDistinguishingParameterException;

class ParameterController extends BaseParameterController
{
    /**
     * @var \Shopsys\ShopBundle\Model\Product\Parameter\ParameterFacade
     */
    protected $parameterFacade;

    /**
     * @Route("/product/parameter/delete/{id}", requirements={"id" = "\d+"})
     * @CsrfProtection
     * @param int $id
     */
    public function deleteAction($id)
    {
        $parameter = null;
        try {
            $parameter = $this->parameterFacade->getById($id);
            $this->parameterFacade->deleteById($id);

            $this->getFlashMessageSender()->addSuccessFlashTwig(
                t('Parameter <strong>{{ name }}</strong> deleted'),
                [
                    'name' => $parameter->getName(),
                ]
            );
        } catch (ParameterNotFoundException $ex) {
            $this->getFlashMessageSender()->addErrorFlash(t('Selected parameter doesn\'t exist.'));
        } catch (ParameterUsedAsDistinguishingParameterException $exception) {
            $productsWithDistinguishingParameter = $this->parameterFacade->getProductsWithDistinguishingParameter($parameter);

            $productIdsWithDistinguishingParameter = implode(
                ', ',
                array_map(function (Product $product) {
                    return $product->getId();
                }, $productsWithDistinguishingParameter)
            );

            $this->getFlashMessageSender()->addErrorFlash(t(
                'Parametr nelze odstranit, protože se používá jako rozlišující parametr pro produkty s ID'
            ) . ' - ' . $productIdsWithDistinguishingParameter);
        }

        return $this->redirectToRoute('admin_parameter_list');
    }
}
