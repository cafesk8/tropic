<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Form\Front\WatchDog\WatchDogFormType;
use App\Model\Customer\User\CustomerUser;
use App\Model\Pricing\Group\PricingGroupFacade;
use App\Model\Product\Pricing\ProductPriceCalculation;
use App\Model\Product\ProductFacade;
use App\Model\WatchDog\WatchDogFacade;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class WatchDogController extends FrontBaseController
{
    /**
     * @var \App\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @var \App\Model\WatchDog\WatchDogFacade
     */
    private $watchDogFacade;

    /**
     * @var \App\Model\Pricing\Group\PricingGroupFacade
     */
    private $pricingGroupFacade;

    /**
     * @var \App\Model\Product\Pricing\ProductPriceCalculation
     */
    private $productPriceCalculation;

    /**
     * @param \App\Model\WatchDog\WatchDogFacade $watchDogFacade
     * @param \App\Model\Product\ProductFacade $productFacade
     * @param \App\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade
     * @param \App\Model\Product\Pricing\ProductPriceCalculation $productPriceCalculation
     */
    public function __construct(
        WatchDogFacade $watchDogFacade,
        ProductFacade $productFacade,
        PricingGroupFacade $pricingGroupFacade,
        ProductPriceCalculation $productPriceCalculation
    ) {
        $this->watchDogFacade = $watchDogFacade;
        $this->productFacade = $productFacade;
        $this->pricingGroupFacade = $pricingGroupFacade;
        $this->productPriceCalculation = $productPriceCalculation;
    }

    /**
     * @param int $productId
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function windowFormAction(int $productId): Response
    {
        return $this->render('Front/Content/WatchDog/windowForm.html.twig', [
            'form' => $this->createWatchDogForm($productId)->createView(),
            'product' => $this->productFacade->getById($productId),
        ]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function subscribeAction(Request $request): Response
    {
        $productId = (int)$request->request->get('watch_dog_form')['productId'];
        $product = $this->productFacade->getById($productId);
        $form = $this->createWatchDogForm($productId);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \App\Model\Customer\User\CustomerUser|null $user */
            $user = $this->getUser();
            $pricingGroup = $this->pricingGroupFacade->getCurrentPricingGroup($user);

            if ($product->isInAnySaleStock()) {
                $originalPrice = $this->productPriceCalculation->calculatePrice(
                    $product,
                    $pricingGroup->getDomainId(),
                    $this->pricingGroupFacade->getSalePricePricingGroup($pricingGroup->getDomainId())
                )->getPriceWithVat();
            } else {
                $originalPrice = $this->productPriceCalculation->calculatePrice($product, $pricingGroup->getDomainId(), $pricingGroup)->getPriceWithVat();
            }

            /** @var \App\Model\WatchDog\WatchDogData $watchDogData */
            $watchDogData = $form->getData();
            $watchDogData->product = $product;
            $watchDogData->pricingGroup = $pricingGroup;
            $watchDogData->originalPrice = $originalPrice;
            $this->watchDogFacade->create($watchDogData);
            $this->addSuccessFlash(t('Váš email byl úspěšně přidán na seznam příjemců hlídače ceny a dostupnosti'));
        }

        return $this->redirect($this->generateUrl('front_product_detail', ['id' => $productId]));
    }

    /**
     * @param int $productId
     * @return \Symfony\Component\Form\FormInterface
     */
    private function createWatchDogForm(int $productId): FormInterface
    {
        /** @var \App\Model\Customer\User\CustomerUser|null $user */
        $user = $this->getUser();

        return $this->createForm(WatchDogFormType::class, null, [
            'action' => $this->generateUrl('front_watch_dog_subscribe'),
            'email' => $user instanceof CustomerUser ? $user->getEmail() : null,
            'product' => $this->productFacade->getById($productId),
        ]);
    }
}
