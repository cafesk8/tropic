<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Front;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Cart\CartFacade;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\CurrentPromoCodeFacade;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFacade;
use Shopsys\FrameworkBundle\Twig\DateTimeFormatterExtension;
use Shopsys\FrameworkBundle\Twig\PriceExtension;
use Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode;
use Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeData;
use Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeFacade;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class PromoCodeController extends FrontBaseController
{
    public const PROMO_CODE_PARAMETER = 'code';

    /**
     * @var \Shopsys\ShopBundle\Model\Order\PromoCode\CurrentPromoCodeFacade
     */
    private $currentPromoCodeFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCodeFacade
     */
    private $promoCodeFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Twig\DateTimeFormatterExtension
     */
    private $dateTimeFormatterExtension;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Cart\CartFacade
     */
    private $cartFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Twig\PriceExtension
     */
    private $priceExtension;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFacade
     */
    private $currencyFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Order\PromoCode\CurrentPromoCodeFacade $currentPromoCodeFacade
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeFacade $promoCodeFacade
     * @param \Shopsys\FrameworkBundle\Twig\DateTimeFormatterExtension $dateTimeFormatterExtension
     * @param \Shopsys\FrameworkBundle\Model\Cart\CartFacade $cartFacade
     * @param \Shopsys\FrameworkBundle\Twig\PriceExtension $priceExtension
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFacade $currencyFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(
        CurrentPromoCodeFacade $currentPromoCodeFacade,
        PromoCodeFacade $promoCodeFacade,
        DateTimeFormatterExtension $dateTimeFormatterExtension,
        CartFacade $cartFacade,
        PriceExtension $priceExtension,
        CurrencyFacade $currencyFacade,
        Domain $domain
    ) {
        $this->currentPromoCodeFacade = $currentPromoCodeFacade;
        $this->promoCodeFacade = $promoCodeFacade;
        $this->dateTimeFormatterExtension = $dateTimeFormatterExtension;
        $this->cartFacade = $cartFacade;
        $this->priceExtension = $priceExtension;
        $this->currencyFacade = $currencyFacade;
        $this->domain = $domain;
    }

    public function indexAction()
    {
        return $this->render('@ShopsysShop/Front/Content/Order/PromoCode/index.html.twig', [
            'validEnteredPromoCode' => $this->currentPromoCodeFacade->getValidEnteredPromoCodeOrNull(),
            'currency' => $this->currencyFacade->getDomainDefaultCurrencyByDomainId($this->domain->getId()),
        ]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function applyAction(Request $request)
    {
        $promoCodeCode = $request->get(self::PROMO_CODE_PARAMETER);

        /** @var \Shopsys\ShopBundle\Model\Cart\Cart $cart */
        $cart = $this->cartFacade->getCartOfCurrentCustomerCreateIfNotExists();

        /** @var \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode $promoCode */
        $promoCode = $this->promoCodeFacade->findPromoCodeByCode($promoCodeCode);

        try {
            $this->currentPromoCodeFacade->setEnteredPromoCode($promoCodeCode, $cart->getTotalWatchedPriceOfProducts());
        } catch (\Shopsys\FrameworkBundle\Model\Order\PromoCode\Exception\InvalidPromoCodeException $ex) {
            return new JsonResponse([
                'result' => false,
                'message' => t('{{title}} není platný. Prosím, zkontrolujte ho.', ['{{title}}' => $this->getErrorMessageTitle()]),
            ]);
        } catch (\Shopsys\ShopBundle\Model\Order\PromoCode\Exception\UsageLimitPromoCodeException $ex) {
            return new JsonResponse([
                'result' => false,
                'message' => t('{{title}} byl již vyčerpán.', ['{{title}}' => $this->getErrorMessageTitle($promoCode)]),
            ]);
        } catch (\Shopsys\ShopBundle\Model\Order\PromoCode\Exception\PromoCodeIsNotValidNow $ex) {
            $message = $this->getPromoCodeIsNotValidMessage($request, $promoCode);
            return new JsonResponse([
                'result' => false,
                'message' => $message,
            ]);
        } catch (\Shopsys\ShopBundle\Model\Order\PromoCode\Exception\MinimalOrderValueException $ex) {
            return new JsonResponse([
                'result' => false,
                'message' => t('Pro využití slevového kódu musíte nakoupit aspoň za %price%.', [
                    '%price%' => $this->priceExtension->priceFilter($promoCode->getMinOrderValue()),
                ]),
            ]);
        }
        $this->getFlashMessageSender()->addSuccessFlash(t('Promo code added to order'));

        return new JsonResponse(['result' => true]);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode|null $promoCode
     * @return string
     */
    private function getErrorMessageTitle(?PromoCode $promoCode = null): string
    {
        if ($promoCode === null) {
            return t('Slevový kupón nebo certifikát');
        }

        if ($promoCode->getType() === PromoCodeData::TYPE_CERTIFICATE) {
            return t('Dárkový certifikát');
        }

        return t('Slevový kupón');
    }

    public function removeAction()
    {
        $this->currentPromoCodeFacade->removeEnteredPromoCode();
        $this->getFlashMessageSender()->addSuccessFlash(t('Promo code removed from order'));

        return $this->redirectToRoute('front_cart');
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode $promoCode
     * @return string
     */
    private function getPromoCodeIsNotValidMessage(Request $request, PromoCode $promoCode): string
    {
        if ($promoCode->getValidFrom() !== null && $promoCode->getValidTo() !== null) { // FROM and TO dates are filled
            $message = t('{{title}} nemůžete uplatnit. Jeho platnost je od {{validityFrom}} do {{validityTo}}.', [
                '{{validityFrom}}' => $this->dateTimeFormatterExtension->formatDate(
                    $promoCode->getValidFrom(),
                    $request->getLocale()
                ),
                '{{validityTo}}' => $this->dateTimeFormatterExtension->formatDate(
                    $promoCode->getValidTo(),
                    $request->getLocale()
                ),
                '{{title}}' => $this->getErrorMessageTitle($promoCode),
            ]);
        } elseif ($promoCode->getValidFrom() !== null && $promoCode->getValidTo() === null) { // Only FROM date is filled
            $message = t('{{title}} nemůžete uplatnit. Jeho platnost je od {{validityFrom}}.', [
                '{{validityFrom}}' => $this->dateTimeFormatterExtension->formatDate(
                    $promoCode->getValidFrom(),
                    $request->getLocale()
                ),
                '{{title}}' => $this->getErrorMessageTitle($promoCode),
            ]);
        } else { // Only TO date is filled
            $message = t('{{title}} nemůžete uplatnit. Jeho platnost byla do {{validityTo}}.', [
                '{{validityTo}}' => $this->dateTimeFormatterExtension->formatDate(
                    $promoCode->getValidTo(),
                    $request->getLocale()
                ),
                '{{title}}' => $this->getErrorMessageTitle($promoCode),
            ]);
        }

        return $message;
    }
}
