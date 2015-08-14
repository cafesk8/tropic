<?php

namespace SS6\ShopBundle\Controller\Front;

use Doctrine\ORM\EntityManager;
use SS6\ShopBundle\Component\Controller\FrontBaseController;
use SS6\ShopBundle\Form\Front\Registration\NewPasswordFormType;
use SS6\ShopBundle\Form\Front\Registration\RegistrationFormType;
use SS6\ShopBundle\Form\Front\Registration\ResetPasswordFormType;
use SS6\ShopBundle\Model\Customer\CustomerEditFacade;
use SS6\ShopBundle\Model\Customer\RegistrationFacade;
use SS6\ShopBundle\Model\Customer\User;
use SS6\ShopBundle\Model\Customer\UserDataFactory;
use SS6\ShopBundle\Model\Domain\Domain;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class RegistrationController extends FrontBaseController {

	/**
	 * @var \Doctrine\ORM\EntityManager
	 */
	private $em;

	/**
	 * @var \SS6\ShopBundle\Model\Customer\CustomerEditFacade
	 */
	private $customerEditFacade;

	/**
	 * @var \SS6\ShopBundle\Model\Customer\RegistrationFacade
	 */
	private $registrationFacade;

	/**
	 * @var \SS6\ShopBundle\Model\Customer\UserDataFactory
	 */
	private $userDataFactory;

	/**
	 * @var \SS6\ShopBundle\Model\Domain\Domain
	 */
	private $domain;

	public function __construct(
		Domain $domain,
		UserDataFactory $userDataFactory,
		CustomerEditFacade $customerEditFacade,
		RegistrationFacade $registrationFacade,
		EntityManager $em
	) {
		$this->domain = $domain;
		$this->userDataFactory = $userDataFactory;
		$this->customerEditFacade = $customerEditFacade;
		$this->registrationFacade = $registrationFacade;
		$this->em = $em;
	}

	public function registerAction(Request $request) {
		$form = $this->createForm(new RegistrationFormType());

		try {
			$userData = $this->userDataFactory->createDefault($this->domain->getId());

			$form->setData($userData);
			$form->handleRequest($request);

			if ($form->isValid()) {
				$userData = $form->getData();
				$userData->domainId = $this->domain->getId();
				$user = $this->customerEditFacade->register($userData);

				$this->login($user);

				$this->getFlashMessageSender()->addSuccessFlash('Byli jste úspěšně zaregistrováni');
				return $this->redirect($this->generateUrl('front_homepage'));
			}
		} catch (\SS6\ShopBundle\Model\Customer\Exception\DuplicateEmailException $e) {
			$form->get('email')->addError(new FormError('V databázi se již nachází zákazník s tímto e-mailem'));
		}

		if ($form->isSubmitted() && !$form->isValid()) {
			$this->getFlashMessageSender()->addErrorFlash('Prosím zkontrolujte si správnost vyplnění všech údajů');
		}

		return $this->render('@SS6Shop/Front/Content/Registration/register.html.twig', [
			'form' => $form->createView(),
		]);
	}

	/**
	 * @param \SS6\ShopBundle\Model\Customer\User $user
	 */
	private function login(User $user) {
		$token = new UsernamePasswordToken($user, $user->getPassword(), 'frontend', $user->getRoles());
		$this->get('security.token_storage')->setToken($token);

		// dispatch the login event
		$request = $this->get('request');
		$event = new InteractiveLoginEvent($request, $token);
		$this->get('event_dispatcher')->dispatch('security.interactive_login', $event);
	}

	public function resetPasswordAction(Request $request) {
		$form = $this->createForm(new ResetPasswordFormType());

		$form->handleRequest($request);

		if ($form->isValid()) {
			$formData = $form->getData();
			$email = $formData['email'];

			try {
				$this->em->beginTransaction();
				$this->registrationFacade->resetPassword($email, $this->domain->getId());
				$this->em->commit();

				$this->getFlashMessageSender()->addSuccessFlashTwig(
					'Odkaz pro vyresetování hesla byl zaslán na email <strong>{{ email }}</strong>.',
					[
						'email' => $email,
					]
				);
				return $this->redirect($this->generateUrl('front_registration_reset_password'));
			} catch (\SS6\ShopBundle\Model\Customer\Exception\UserNotFoundByEmailAndDomainException $ex) {
				$this->getFlashMessageSender()->addErrorFlashTwig(
					'Zákazník s emailovou adresou <strong>{{ email }}</strong> neexistuje.'
					. ' <a href="{{ registrationLink }}">Zaregistrovat</a>', [
						'email' => $ex->getEmail(),
						'registrationLink' => $this->generateUrl('front_registration_register'),
					]);
			} catch (\Exception $ex) {
				$this->em->rollback();
				throw $ex;
			}
		}

		return $this->render('@SS6Shop/Front/Content/Registration/resetPassword.html.twig', [
			'form' => $form->createView(),
		]);
	}

	public function setNewPasswordAction(Request $request) {
		$email = $request->query->get('email');
		$hash = $request->query->get('hash');

		if (!$this->registrationFacade->isResetPasswordHashValid($email, $this->domain->getId(), $hash)) {
			$this->getFlashMessageSender()->addErrorFlash('Platnost odkazu pro změnu hesla vypršela.');
			return $this->redirect($this->generateUrl('front_homepage'));
		}

		$form = $this->createForm(new NewPasswordFormType());

		$form->handleRequest($request);

		if ($form->isValid()) {
			$formData = $form->getData();

			$newPassword = $formData['newPassword'];

			try {
				$this->em->beginTransaction();
				$user = $this->registrationFacade->setNewPassword($email, $this->domain->getId(), $hash, $newPassword);
				$this->login($user);
				$this->em->commit();
			} catch (\SS6\ShopBundle\Model\Customer\Exception\UserNotFoundByEmailAndDomainException $ex) {
				$this->em->rollback();
				$this->getFlashMessageSender()->addErrorFlashTwig('Zákazník s emailovou adresou <strong>{{ email }}</strong> neexistuje.'
					. ' <a href="{{ registrationLink }}">Zaregistrovat</a>', [
						'email' => $ex->getEmail(),
						'registrationLink' => $this->generateUrl('front_registration_register'),
					]);
			} catch (\SS6\ShopBundle\Model\Customer\Exception\InvalidResetPasswordHashException $ex) {
				$this->em->rollback();
				$this->getFlashMessageSender()->addErrorFlash('Platnost odkazu pro změnu hesla vypršela.');
			} catch (\Exception $ex) {
				$this->em->rollback();
				throw $ex;
			}

			$this->getFlashMessageSender()->addSuccessFlash('Heslo bylo úspěšně změněno');
			return $this->redirect($this->generateUrl('front_homepage'));
		}

		return $this->render('@SS6Shop/Front/Content/Registration/setNewPassword.html.twig', [
			'form' => $form->createView(),
		]);
	}

}
