<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Admin;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Shopsys\FrameworkBundle\Component\Grid\GridFactory;
use Shopsys\FrameworkBundle\Component\Grid\QueryBuilderDataSource;
use Shopsys\FrameworkBundle\Controller\Admin\AdminBaseController;
use Shopsys\FrameworkBundle\Model\Administrator\AdministratorFacade;
use Shopsys\FrameworkBundle\Model\Administrator\AdministratorGridFacade;
use Shopsys\ShopBundle\Form\Admin\TransferIssueSearchFormType;
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

        $form = $this->createForm(TransferIssueSearchFormType::class);
        $form->handleRequest($request);

        if ($form->isValid()) {
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

        $grid->addColumn('transferName', 'transferName', t('Název přenosu'), true);
        $grid->addColumn('transferIdentifier', 'transferIdentifier', t('Interní identifikátor přenosu'), true);
        $grid->addColumn('message', 'message', t('Text zprávy'));
        $grid->addColumn('createdAt', 'createdAt', t('Datum a čas'), true);

        $grid->setTheme('@ShopsysShop/Admin/Content/Transfer/Issue/listGrid.html.twig');

        $this->administratorGridFacade->restoreAndRememberGridLimit($administrator, $grid);

        $this->transferIssueFacade->logTransferIssuesVisitByAdministrator($administrator);

        return $this->render('ShopsysShopBundle:Admin/Content/Transfer/Issue:list.html.twig', [
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
        /* @var $administrator \Shopsys\ShopBundle\Model\Administrator\Administrator */

        $queryBuilder = $this->transferIssueFacade->getTransferIssuesWithContextByGroupIdQueryBuilderForDataGrid($groupId);
        $dataSource = new QueryBuilderDataSource($queryBuilder, 'id');
        $grid = $this->gridFactory->create('transferIssueAggregatedList', $dataSource);
        $grid->enablePaging();
        $grid->setDefaultOrder('createdAt DESC, id');

        $grid->addColumn('context', 'ti.context', t('Upřesňující informace'));
        $grid->addColumn('createdAt', 'ti.createdAt', t('Datum a čas'), true);

        $this->administratorGridFacade->restoreAndRememberGridLimit($administrator, $grid);

        return $this->render('ShopsysShopBundle:Admin/Content/Transfer/Issue:detailedList.html.twig', [
            'gridView' => $grid->createView(),
            'message' => $message,
        ]);
    }
}
