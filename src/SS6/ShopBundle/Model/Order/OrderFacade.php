<?php

namespace SS6\ShopBundle\Model\Order;

use Doctrine\ORM\EntityManager;
use SS6\ShopBundle\Form\Admin\Order\OrderFormData as AdminOrderFormData;
use SS6\ShopBundle\Form\Front\Order\OrderFormData as FrontOrderFormData;
use SS6\ShopBundle\Model\Cart\Cart;
use SS6\ShopBundle\Model\Customer\User;
use SS6\ShopBundle\Model\Customer\UserRepository;
use SS6\ShopBundle\Model\Order\Item\OrderPayment;
use SS6\ShopBundle\Model\Order\Item\OrderProduct;
use SS6\ShopBundle\Model\Order\Item\OrderTransport;
use SS6\ShopBundle\Model\Order\OrderNumberSequenceRepository;
use SS6\ShopBundle\Model\Order\Order;
use SS6\ShopBundle\Model\Order\OrderService;
use SS6\ShopBundle\Model\Order\Status\OrderStatusRepository;

class OrderFacade {

	/**
	 * @var \Doctrine\ORM\EntityManager
	 */
	private $em;

	/**
	 * @var \SS6\ShopBundle\Model\Order\OrderNumberSequenceRepository
	 */
	private $orderNumberSequenceRepository;

	/**
	 * @var \SS6\ShopBundle\Model\Cart\Cart
	 */
	private $cart;

	/**
	 * @var \SS6\ShopBundle\Model\Order\OrderRepository
	 */
	private $orderRepository;

	/**
	 * @var \SS6\ShopBundle\Model\Order\OrderService
	 */
	private $orderService;

	/**
	 * @var \SS6\ShopBundle\Model\Customer\UserRepository
	 */
	private $userRepository;

	/**
	 * @var \SS6\ShopBundle\Model\Order\Status\OrderStatusRepository
	 */
	private $orderStatusRepository;

	/**
	 * @param \Doctrine\ORM\EntityManager $em
	 * @param \SS6\ShopBundle\Model\Order\OrderNumberSequenceRepository $orderNumberSequenceRepository
	 * @param \SS6\ShopBundle\Model\Cart\Cart $cart
	 * @param \SS6\ShopBundle\Model\Customer\UserRepository $userRepository
	 */
	public function __construct(EntityManager $em, OrderNumberSequenceRepository $orderNumberSequenceRepository,
		Cart $cart, OrderRepository $orderRepository, OrderService $orderService, UserRepository $userRepository,
		OrderStatusRepository $orderStatusRepository
	) {
		$this->em = $em;
		$this->orderNumberSequenceRepository = $orderNumberSequenceRepository;
		$this->cart = $cart;
		$this->orderRepository = $orderRepository;
		$this->orderService = $orderService;
		$this->userRepository = $userRepository;
		$this->orderStatusRepository = $orderStatusRepository;
	}

	/**
	 * @param $orderFormData \SS6\ShopBundle\Form\Front\Order\OrderFormData
	 * @param $user \SS6\ShopBundle\Model\Customer\User|null
	 * @return \SS6\ShopBundle\Model\Order\Order
	 */
	public function createOrder(FrontOrderFormData $orderFormData, User $user = null) {
		$orderStatus = $this->orderStatusRepository->getDefault();
		$orderNumber = $this->orderNumberSequenceRepository->getNextNumber();

		$order = $this->orderService->createOrder(
			$orderFormData,
			$orderNumber,
			$orderStatus,
			$user
		);

		$this->fillOrderItems($order, $this->cart);

		$this->em->persist($order);
		$this->em->flush();

		return $order;
	}

	/**
	 * @param \SS6\ShopBundle\Model\Order\Order $order
	 * @param \SS6\ShopBundle\Model\Cart\Cart $cart
	 */
	private function fillOrderItems(Order $order, Cart $cart) {
		$cartItems = $cart->getItems();
		foreach ($cartItems as $cartItem) {
			/* @var $cartItem \SS6\ShopBundle\Model\Cart\CartItem */
			$orderItem = new OrderProduct($order,
				$cartItem->getProduct()->getName(),
				$cartItem->getProduct()->getPrice(),
				$cartItem->getQuantity(),
				$cartItem->getProduct()
			);
			$order->addItem($orderItem);
			$this->em->persist($orderItem);
		}

		$payment = $order->getPayment();
		$orderPayment = new OrderPayment($order,
			$payment->getName(),
			$payment->getPrice(),
			1,
			$payment
		);
		$order->addItem($orderPayment);
		$this->em->persist($orderPayment);

		$transport = $order->getTransport();
		$orderTransport = new OrderTransport($order,
			$transport->getName(),
			$transport->getPrice(),
			1,
			$transport
		);
		$order->addItem($orderTransport);
		$this->em->persist($orderTransport);
	}

	/**
	 *
	 * @param int $orderId
	 * @param \SS6\ShopBundle\Form\Admin\Order\OrderFormData $orderData
	 * @return \SS6\ShopBundle\Model\Order\Order
	 */
	public function edit($orderId, AdminOrderFormData $orderData) {
		$order = $this->orderRepository->getById($orderId);
		$orderStatus = $this->orderStatusRepository->getById($orderData->getStatusId());
		$user = null;
		if ($orderData->getCustomerId() !== null) {
			$user = $this->userRepository->getUserById($orderData->getCustomerId());
		}

		$this->orderService->editOrder($order, $orderData, $orderStatus, $user);

		$this->em->flush();
		return $order;
	}

	/**
	 * @param \SS6\ShopBundle\Form\Front\Order\OrderFormData $orderFormData
	 * @param \SS6\ShopBundle\Model\Customer\User $user
	 */
	public function prefillOrderFormData(FrontOrderFormData $orderFormData, User $user) {
		$this->orderService->prefillFrontFormData($orderFormData, $user);
	}

	/**
	 * @param int $orderId
	 */
	public function deleteById($orderId) {
		$order = $this->orderRepository->getById($orderId);
		$order->markAsDeleted();
		$this->em->flush();
	}
}
