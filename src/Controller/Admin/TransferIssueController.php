<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Form\Admin\TransferIssueSearchFormType;
use App\Model\Transfer\Issue\TransferIssueFacade;
use Shopsys\FrameworkBundle\Component\Grid\GridFactory;
use Shopsys\FrameworkBundle\Component\Grid\QueryBuilderDataSource;
use Shopsys\FrameworkBundle\Controller\Admin\AdminBaseController;
use Shopsys\FrameworkBundle\Model\Administrator\AdministratorFacade;
use Shopsys\FrameworkBundle\Model\Administrator\AdministratorGridFacade;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
     * @var \App\Model\Transfer\Issue\TransferIssueFacade
     */
    private $transferIssueFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Grid\GridFactory
     */
    private $gridFactory;

    /**
     * @param \App\Model\Transfer\Issue\TransferIssueFacade $transferIssueFacade
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
        /* @var $administrator \App\Model\Administrator\Administrator */

        $queryBuilder = $this->transferIssueFacade->getTransferIssuesQueryBuilderForDataGrid();

        $form = $this->createForm(TransferIssueSearchFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $filteredTransfer = $form->getData()['transfer'];

            if ($filteredTransfer !== null) {
                $queryBuilder
                    ->andWhere('ti.transfer = :transfer')
                    ->setParameter('transfer', $filteredTransfer);
            }
        }
        $dataSource = new QueryBuilderDataSource($queryBuilder, 'id');

        $grid = $this->gridFactory->create('transferIssueList', $dataSource);
        $grid->enablePaging();
        $grid->setDefaultOrder('createdAt DESC, id');

        $grid->addColumn('transferName', 'transferName', t('N??zev p??enosu'), true);
        $grid->addColumn('transferIdentifier', 'transferIdentifier', t('Intern?? identifik??tor p??enosu'), true);
        $grid->addColumn('message', 'message', t('Text zpr??vy'));
        $grid->addColumn('createdAt', 'createdAt', t('Datum a ??as'), true);

        $grid->setTheme('Admin/Content/Transfer/Issue/listGrid.html.twig');

        $this->administratorGridFacade->restoreAndRememberGridLimit($administrator, $grid);

        $this->transferIssueFacade->logTransferIssuesVisitByAdministrator($administrator);

        return $this->render('Admin/Content/Transfer/Issue/list.html.twig', [
            'form' => $form->createView(),
            'gridView' => $grid->createView(),
        ]);
    }

    /**
     * @Route("/transfer-issues/detail/{groupId}/{message}")
     * @param string $groupId
     * @param string $message
     * @throws \Shopsys\FrameworkBundle\Component\Grid\Exception\DuplicateColumnIdException
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function detailedListAction(string $groupId, string $message): Response
    {
        $administrator = $this->getUser();
        /* @var $administrator \App\Model\Administrator\Administrator */

        $queryBuilder = $this->transferIssueFacade->getTransferIssuesWithContextByGroupIdAndMessageQueryBuilderForDataGrid($groupId, $message);
        $dataSource = new QueryBuilderDataSource($queryBuilder, 'id');
        $grid = $this->gridFactory->create('transferIssueAggregatedList', $dataSource);
        $grid->enablePaging();
        $grid->setDefaultOrder('createdAt DESC, id');

        $grid->addColumn('context', 'ti.context', t('Up??es??uj??c?? informace'));
        $grid->addColumn('createdAt', 'ti.createdAt', t('Datum a ??as'), true);

        $this->administratorGridFacade->restoreAndRememberGridLimit($administrator, $grid);

        return $this->render('Admin/Content/Transfer/Issue/detailedList.html.twig', [
            'gridView' => $grid->createView(),
            'message' => $message,
        ]);
    }
}
