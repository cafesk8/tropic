<?php

namespace Shopsys\ShopBundle\Controller\Front;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Customer\CustomerDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Customer\CustomerFacade;
use Shopsys\FrameworkBundle\Model\Order\Item\OrderItemPriceCalculation;
use Shopsys\FrameworkBundle\Model\Order\OrderFacade;
use Shopsys\FrameworkBundle\Model\Security\LoginAsUserFacade;
use Shopsys\FrameworkBundle\Model\Security\Roles;
use Shopsys\ShopBundle\Form\Front\Customer\CustomerFormType;
use Shopsys\ShopBundle\Model\BushmanClub\CurrentBushmanClubPointPeriods;
use Symfony\Component\HttpFoundation\Request;

class CustomerController extends FrontBaseController
{
    /**
     * @var \Shopsys\FrameworkBundle\Model\Customer\CustomerFacade
     */
    private $customerFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Order\Item\OrderItemPriceCalculation
     */
    private $orderItemPriceCalculation;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Order\OrderFacade
     */
    private $orderFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Security\LoginAsUserFacade
     */
    private $loginAsUserFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Customer\CustomerDataFactoryInterface
     */
    private $customerDataFactory;

    /**
     * @var \Shopsys\ShopBundle\Model\BushmanClub\CurrentBushmanClubPointPeriods
     */
    private $bushmanClubPointPeriodSettings;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Customer\CustomerFacade $customerFacade
     * @param \Shopsys\FrameworkBundle\Model\Order\OrderFacade $orderFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\Order\Item\OrderItemPriceCalculation $orderItemPriceCalculation
     * @param \Shopsys\FrameworkBundle\Model\Security\LoginAsUserFacade $loginAsUserFacade
     * @param \Shopsys\FrameworkBundle\Model\Customer\CustomerDataFactoryInterface $customerDataFactory
     * @param \Shopsys\ShopBundle\Model\BushmanClub\CurrentBushmanClubPointPeriods $bushmanClubPointPeriodSettings
     */
    public function __construct(
        CustomerFacade $customerFacade,
        OrderFacade $orderFacade,
        Domain $domain,
        OrderItemPriceCalculation $orderItemPriceCalculation,
        LoginAsUserFacade $loginAsUserFacade,
        CustomerDataFactoryInterface $customerDataFactory,
        CurrentBushmanClubPointPeriods $bushmanClubPointPeriodSettings
    ) {
        $this->customerFacade = $customerFacade;
        $this->orderFacade = $orderFacade;
        $this->domain = $domain;
        $this->orderItemPriceCalculation = $orderItemPriceCalculation;
        $this->loginAsUserFacade = $loginAsUserFacade;
        $this->customerDataFactory = $customerDataFactory;
        $this->bushmanClubPointPeriodSettings = $bushmanClubPointPeriodSettings;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function editAction(Request $request)
    {
        if (!$this->isGranted(Roles::ROLE_LOGGED_CUSTOMER)) {
            $this->getFlashMessageSender()->addErrorFlash(t('You have to be logged in to enter this page'));
            return $this->redirectToRoute('front_login');
        }

        $user = $this->getUser();
        $customerData = $this->customerDataFactory->createFromUser($user);

        $form = $this->createForm(CustomerFormType::class, $customerData, [
            'domain_id' => $this->domain->getId(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $customerData = $form->getData();

            $this->customerFacade->editByCustomer($user->getId(), $customerData);

            $this->getFlashMessageSender()->addSuccessFlash(t('Your data had been successfully updated'));
            return $this->redirectToRoute('front_customer_edit');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->getFlashMessageSender()->addErrorFlash(t('Please check the correctness of all data filled.'));
        }

        return $this->render('@ShopsysShop/Front/Content/Customer/edit.html.twig', [
            'form' => $form->createView(),
            'bushmanClubPointPeriods' => $this->bushmanClubPointPeriodSettings->getPeriods(),
            'customerId' => $user->getId(),
        ]);
    }

    public function ordersAction()
    {
        if (!$this->isGranted(Roles::ROLE_LOGGED_CUSTOMER)) {
            $this->getFlashMessageSender()->addErrorFlash(t('You have to be logged in to enter this page'));
            return $this->redirectToRoute('front_login');
        }

        /** @var \Shopsys\FrameworkBundle\Model\Customer\User $user */
        $user = $this->getUser();

        $orders = $this->orderFacade->getCustomerOrderList($user);
        return $this->render('@ShopsysShop/Front/Content/Customer/orders.html.twig', [
            'orders' => $orders,
        ]);
    }

    /**
     * @param string $orderNumber
     */
    public function orderDetailRegisteredAction($orderNumber)
    {
        return $this->orderDetailAction(null, $orderNumber);
    }

    /**
     * @param string $urlHash
     */
    public function orderDetailUnregisteredAction($urlHash)
    {
        return $this->orderDetailAction($urlHash, null);
    }

    /**
     * @param string $urlHash
     * @param string $orderNumber
     */
    private function orderDetailAction($urlHash = null, $orderNumber = null)
    {
        if ($orderNumber !== null) {
            if (!$this->isGranted(Roles::ROLE_LOGGED_CUSTOMER)) {
                $this->getFlashMessageSender()->addErrorFlash(t('You have to be logged in to enter this page'));
                return $this->redirectToRoute('front_login');
            }

            $user = $this->getUser();
            try {
                /** @var \Shopsys\FrameworkBundle\Model\Order\Order $order */
                $order = $this->orderFacade->getByOrderNumberAndUser($orderNumber, $user);
            } catch (\Shopsys\FrameworkBundle\Model\Order\Exception\OrderNotFoundException $ex) {
                $this->getFlashMessageSender()->addErrorFlash(t('Order not found'));
                return $this->redirectToRoute('front_customer_orders');
            }
        } else {
            /** @var \Shopsys\FrameworkBundle\Model\Order\Order $order */
            $order = $this->orderFacade->getByUrlHashAndDomain($urlHash, $this->domain->getId());
        }

        $orderItemTotalPricesById = $this->orderItemPriceCalculation->calculateTotalPricesIndexedById($order->getItems());

        return $this->render('@ShopsysShop/Front/Content/Customer/orderDetail.html.twig', [
            'order' => $order,
            'orderItemTotalPricesById' => $orderItemTotalPricesById,
        ]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function loginAsRememberedUserAction(Request $request)
    {
        try {
            $this->loginAsUserFacade->loginAsRememberedUser($request);
        } catch (\Shopsys\FrameworkBundle\Model\Customer\Exception\UserNotFoundException $e) {
            /** @var \Shopsys\FrameworkBundle\Component\FlashMessage\FlashMessageSender $adminFlashMessageSender */
            $adminFlashMessageSender = $this->get('shopsys.shop.component.flash_message.sender.admin');
            $adminFlashMessageSender->addErrorFlash(t('User not found.'));

            return $this->redirectToRoute('admin_customer_list');
        } catch (\Shopsys\FrameworkBundle\Model\Security\Exception\LoginAsRememberedUserException $e) {
            throw $this->createAccessDeniedException('', $e);
        }

        return $this->redirectToRoute('front_homepage');
    }
}
