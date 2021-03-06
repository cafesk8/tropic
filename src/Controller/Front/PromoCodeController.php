<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Model\Order\PromoCode\Exception\InactivePromoCodeException;
use App\Model\Order\PromoCode\Exception\PromoCodeAlreadyAppliedException;
use App\Model\Order\PromoCode\Exception\PromoCodeIsNotBetterThanOrderLevelDiscountException;
use App\Model\Order\PromoCode\Exception\PromoCodeNotApplicableException;
use App\Model\Order\PromoCode\Exception\PromoCodeNotCombinableException;
use App\Model\Order\PromoCode\Exception\UsageLimitPromoCodeException;
use App\Model\Order\PromoCode\PromoCode;
use App\Model\Order\PromoCode\PromoCodeFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Cart\CartFacade;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\CurrentPromoCodeFacade;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFacade;
use Shopsys\FrameworkBundle\Twig\PriceExtension;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class PromoCodeController extends FrontBaseController
{
    public const PROMO_CODE_PARAMETER = 'code';

    /**
     * @var \App\Model\Order\PromoCode\CurrentPromoCodeFacade
     */
    private $currentPromoCodeFacade;

    /**
     * @var \App\Model\Order\PromoCode\PromoCodeFacade
     */
    private $promoCodeFacade;

    /**
     * @var \App\Model\Cart\CartFacade
     */
    private $cartFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Twig\PriceExtension
     */
    private $priceExtension;

    /**
     * @var \App\Model\Pricing\Currency\CurrencyFacade
     */
    private $currencyFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @param \App\Model\Order\PromoCode\CurrentPromoCodeFacade $currentPromoCodeFacade
     * @param \App\Model\Order\PromoCode\PromoCodeFacade $promoCodeFacade
     * @param \App\Model\Cart\CartFacade $cartFacade
     * @param \Shopsys\FrameworkBundle\Twig\PriceExtension $priceExtension
     * @param \App\Model\Pricing\Currency\CurrencyFacade $currencyFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(
        CurrentPromoCodeFacade $currentPromoCodeFacade,
        PromoCodeFacade $promoCodeFacade,
        CartFacade $cartFacade,
        PriceExtension $priceExtension,
        CurrencyFacade $currencyFacade,
        Domain $domain
    ) {
        $this->currentPromoCodeFacade = $currentPromoCodeFacade;
        $this->promoCodeFacade = $promoCodeFacade;
        $this->cartFacade = $cartFacade;
        $this->priceExtension = $priceExtension;
        $this->currencyFacade = $currencyFacade;
        $this->domain = $domain;
    }

    public function indexAction()
    {
        return $this->render('Front/Content/Order/PromoCode/index.html.twig', [
            'currency' => $this->currencyFacade->getDomainDefaultCurrencyByDomainId($this->domain->getId()),
        ]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function applyAction(Request $request)
    {
        $promoCodeCode = $request->get(self::PROMO_CODE_PARAMETER);

        /** @var \App\Model\Cart\Cart $cart */
        $cart = $this->cartFacade->getCartOfCurrentCustomerUserCreateIfNotExists();

        /** @var \App\Model\Order\PromoCode\PromoCode $promoCode */
        $promoCode = $this->promoCodeFacade->findPromoCodeByCodeAndDomainId($promoCodeCode, $this->domain->getId());

        /** @var \App\Model\Customer\User\CustomerUser|null $customerUser */
        $customerUser = $this->getUser();

        try {
            if ($promoCode instanceof PromoCode) {
                $this->currentPromoCodeFacade->checkApplicability($promoCode, $cart);
            }

            $this->currentPromoCodeFacade->setEnteredPromoCode($promoCodeCode, $cart->getTotalWatchedPriceOfProducts(), $customerUser);
        } catch (\Shopsys\FrameworkBundle\Model\Order\PromoCode\Exception\InvalidPromoCodeException $ex) {
            return new JsonResponse([
                'result' => false,
                'message' => t('{{title}} není platný. Prosím, zkontrolujte ho.', ['{{title}}' => $this->getErrorMessageTitle($promoCode)]),
            ]);
        } catch (UsageLimitPromoCodeException $ex) {
            return new JsonResponse([
                'result' => false,
                'message' => t('{{title}} byl již vyčerpán.', ['{{title}}' => $this->getErrorMessageTitle($promoCode)]),
            ]);
        } catch (InactivePromoCodeException $ex) {
            return new JsonResponse([
                'result' => false,
                'message' => t('{{title}} není aktivní.', ['{{title}}' => $this->getErrorMessageTitle($promoCode)]),
            ]);
        } catch (\App\Model\Order\PromoCode\Exception\PromoCodeIsNotValidNow $ex) {
            $message = $this->getPromoCodeIsNotValidMessage($promoCode);
            return new JsonResponse([
                'result' => false,
                'message' => $message,
            ]);
        } catch (\App\Model\Order\PromoCode\Exception\MinimalOrderValueException $ex) {
            return new JsonResponse([
                'result' => false,
                'message' => t('Pro využití slevového kódu musíte nakoupit aspoň za %price%.', [
                    '%price%' => $this->priceExtension->priceFilter($promoCode->getMinOrderValue()),
                ]),
            ]);
        } catch (\App\Model\Order\PromoCode\Exception\PromoCodeIsOnlyForLoggedCustomers $ex) {
            return new JsonResponse([
                'result' => false,
                'message' => t('Slevový kupón mohou aplikovat pouze přihlášení zákazníci.'),
            ]);
        } catch (PromoCodeNotCombinableException $ex) {
            return new JsonResponse([
                'result' => false,
                'message' => t('Už máš aplikován jiný slevový kupón, a tento kupón nelze kombinovat.'),
            ]);
        } catch (PromoCodeAlreadyAppliedException $ex) {
            return new JsonResponse([
                'result' => false,
                'message' => t('Tento slevový kupón je již v objednávce aplikován.'),
            ]);
        } catch (PromoCodeNotApplicableException $ex) {
            return new JsonResponse([
                'result' => false,
                'message' => t('Tento slevový kupón nelze aplikovat na žádný produkt v košíku.'),
            ]);
        } catch (PromoCodeIsNotBetterThanOrderLevelDiscountException $ex) {
            return new JsonResponse([
                'result' => false,
                'message' => t('Tento slevový kupón neposkytuje výhodnější slevu než aktuálně aktivní sleva na celý nákup.'),
            ]);
        }

        $this->addSuccessFlash(t('Promo code added to order'));

        return new JsonResponse(['result' => true]);
    }

    /**
     * @param \App\Model\Order\PromoCode\PromoCode|null $promoCode
     * @return string
     */
    private function getErrorMessageTitle(?PromoCode $promoCode = null): string
    {
        if ($promoCode === null) {
            return t('Slevový kupón nebo dárkový poukaz');
        }

        if ($promoCode->isTypeGiftCertificate()) {
            return t('Dárkový poukaz');
        }

        return t('Slevový kupón');
    }

    public function removeAction()
    {
        $this->currentPromoCodeFacade->removeEnteredPromoCode();
        $this->addSuccessFlash(t('Promo code removed from order'));

        return $this->redirectToRoute('front_cart');
    }

    /**
     * @param \App\Model\Order\PromoCode\PromoCode $promoCode
     * @return string
     */
    private function getPromoCodeIsNotValidMessage(PromoCode $promoCode): string
    {
        $message = t('{{title}} nemůžete uplatnit. Platnost kupónu vypršela.', [
            '{{title}}' => $this->getErrorMessageTitle($promoCode),
        ]);

        return $message;
    }
}
