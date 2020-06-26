<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\WatchDog\WatchDogGridFactory;
use Shopsys\FrameworkBundle\Controller\Admin\AdminBaseController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WatchDogController extends AdminBaseController
{
    /**
     * @var \App\Model\WatchDog\WatchDogGridFactory
     */
    private $watchDogGridFactory;

    /**
     * @param \App\Model\WatchDog\WatchDogGridFactory $watchDogGridFactory
     */
    public function __construct(WatchDogGridFactory $watchDogGridFactory)
    {
        $this->watchDogGridFactory = $watchDogGridFactory;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/watch-dog/list/")
     */
    public function listAction(): Response
    {
        $grid = $this->watchDogGridFactory->create();

        return $this->render('Admin/Content/WatchDog/list.html.twig', [
            'gridView' => $grid->createView(),
        ]);
    }
}
