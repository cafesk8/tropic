<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Admin;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Shopsys\FrameworkBundle\Component\Grid\GridFactory;
use Shopsys\FrameworkBundle\Component\Grid\QueryBuilderDataSource;
use Shopsys\FrameworkBundle\Controller\Admin\AdminBaseController;
use Shopsys\FrameworkBundle\Model\Administrator\AdministratorFacade;
use Shopsys\FrameworkBundle\Model\Administrator\AdministratorGridFacade;
use Shopsys\ShopBundle\Model\Transfer\Issue\TransferIssueFacade;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TransferIssueController extends AdminBaseController
{
    /**
     * @var \Shopsys\FrameworkBundle\Model\Administrator\AdministratorFacade
     */
    protected $administratorFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Administrator\AdministratorGridFacade
     */
    protected $administratorGridFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Transfer\Issue\TransferIssueFacade
     */
    private $transferIssueFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Grid\GridFactory
     */
    private $gridFactory;

    /**
     * @param \Shopsys\ShopBundle\Model\Transfer\Issue\TransferIssueFacade $transferIssueFacade
     * @param \Shopsys\FrameworkBundle\Component\Grid\GridFactory $gridFactory
     * @param \Shopsys\FrameworkBundle\Model\Administrator\AdministratorFacade $administratorFacade
     * @param \Shopsys\FrameworkBundle\Model\Administrator\AdministratorGridFacade $administratorGridFacade
     */
    public function __construct(
        TransferIssueFacade $transferIssueFacade,
        GridFactory $gridFactory,
        AdministratorFacade $administratorFacade,
        AdministratorGridFacade $administratorGridFacade
    ) {
        $this->transferIssueFacade = $transferIssueFacade;
        $this->gridFactory = $gridFactory;
        $this->administratorFacade = $administratorFacade;
        $this->administratorGridFacade = $administratorGridFacade;
    }

    /**
     * @Route("/transfer-issues/list/")
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction(Request $request): Response
    {
        $administrator = $this->getUser();
        /* @var $administrator \Shopsys\ShopBundle\Model\Administrator\Administrator */

        $queryBuilder = $this->transferIssueFacade->getTransferIssuesQueryBuilderForDataGrid();

        $dataSource = new QueryBuilderDataSource($queryBuilder, 'ti.id');

        $grid = $this->gridFactory->create('transferIssueList', $dataSource);
        $grid->enablePaging();
        $grid->setDefaultOrder('createdAt DESC, id');

        $grid->addColumn('transferName', 't.name', t('Název přenosu'), true);
        $grid->addColumn('transferIdentifier', 't.identifier', t('Interní identifikátor přenosu'), true);
        $grid->addColumn('message', 'ti.message', t('Text zprávy'));
        $grid->addColumn('createdAt', 'ti.createdAt', t('Datum a čas'), true);

        $this->administratorGridFacade->restoreAndRememberGridLimit($administrator, $grid);

        $this->transferIssueFacade->logTransferIssuesVisitByAdministrator($administrator);

        return $this->render('ShopsysShopBundle:Admin/Content/Transfer/Issue:list.html.twig', [
            'gridView' => $grid->createView(),
        ]);
    }
}
