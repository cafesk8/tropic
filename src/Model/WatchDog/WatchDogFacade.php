<?php

declare(strict_types=1);

namespace App\Model\WatchDog;

use App\Component\WatchDog\WatchDogMail;
use App\Model\Pricing\Group\PricingGroupFacade;
use App\Model\Product\Pricing\ProductPriceCalculation;
use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Model\Mail\Mailer;
use Shopsys\FrameworkBundle\Model\Mail\MailTemplateFacade;

class WatchDogFacade
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;

    /**
     * @var \App\Model\WatchDog\WatchDogRepository
     */
    private $watchDogRepository;

    /**
     * @var \App\Component\WatchDog\WatchDogMail
     */
    private $watchDogMail;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Mail\MailTemplateFacade
     */
    private $mailTemplateFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Mail\Mailer
     */
    private $mailer;

    /**
     * @var \App\Model\Pricing\Group\PricingGroupFacade
     */
    private $pricingGroupFacade;

    /**
     * @var \App\Model\Product\Pricing\ProductPriceCalculation
     */
    private $productPriceCalculation;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \App\Model\WatchDog\WatchDogRepository $watchDogRepository
     * @param \App\Component\WatchDog\WatchDogMail $watchDogMail
     * @param \Shopsys\FrameworkBundle\Model\Mail\MailTemplateFacade $mailTemplateFacade
     * @param \Shopsys\FrameworkBundle\Model\Mail\Mailer $mailer
     * @param \App\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade
     * @param \App\Model\Product\Pricing\ProductPriceCalculation $productPriceCalculation
     */
    public function __construct(
        EntityManagerInterface $em,
        WatchDogRepository $watchDogRepository,
        WatchDogMail $watchDogMail,
        MailTemplateFacade $mailTemplateFacade,
        Mailer $mailer,
        PricingGroupFacade $pricingGroupFacade,
        ProductPriceCalculation $productPriceCalculation
    ) {
        $this->em = $em;
        $this->watchDogRepository = $watchDogRepository;
        $this->watchDogMail = $watchDogMail;
        $this->mailTemplateFacade = $mailTemplateFacade;
        $this->mailer = $mailer;
        $this->pricingGroupFacade = $pricingGroupFacade;
        $this->productPriceCalculation = $productPriceCalculation;
    }

    /**
     * @param \App\Model\WatchDog\WatchDogData $watchDogData
     * @return \App\Model\WatchDog\WatchDog
     */
    public function create(WatchDogData $watchDogData): WatchDog
    {
        $watchDog = new WatchDog($watchDogData);
        $this->em->persist($watchDog);
        $this->em->flush();

        return $watchDog;
    }

    /**
     * @return \App\Model\WatchDog\WatchDog[]
     */
    public function getAllVisible(): array
    {
        return $this->watchDogRepository->getAllVisible();
    }

    /**
     * @param \App\Model\WatchDog\WatchDog $watchDog
     * @return bool
     */
    public function shouldBeSent(WatchDog $watchDog): bool
    {
        $product = $watchDog->getProduct();
        $domainId = $watchDog->getPricingGroup()->getDomainId();
        $pricingGroup = $product->isInAnySaleStock() ? $this->pricingGroupFacade->getSalePricePricingGroup($domainId) : $watchDog->getPricingGroup();

        if (!$product->isShownOnDomain($domainId)) {
            return false;
        }

        if ($watchDog->isAvailabilityWatcher() && $product->getCalculatedSellingDenied()) {
            return false;
        }

        if (!$watchDog->isPriceWatcher()) {
            return true;
        }

        $productPrice = $this->productPriceCalculation->calculatePrice($product, $domainId, $pricingGroup);

        if ($watchDog->getTargetPrice() === null) {
            if ($watchDog->getOriginalPrice()->isGreaterThan($productPrice->getPriceWithVat())) {
                return true;
            }

            return false;
        }

        if ($watchDog->getTargetPrice()->isGreaterThanOrEqualTo($productPrice->getPriceWithVat())) {
            return true;
        }

        return false;
    }

    /**
     * @param \App\Model\WatchDog\WatchDog $watchDog
     */
    public function sendMail(WatchDog $watchDog): void
    {
        $mailTemplate = $this->mailTemplateFacade->get(WatchDogMail::MAIL_TEMPLATE_WATCH_DOG, $watchDog->getPricingGroup()->getDomainId());
        $messageData = $this->watchDogMail->createMessage($mailTemplate, $watchDog);
        $this->mailer->send($messageData);
    }

    /**
     * @param \App\Model\WatchDog\WatchDog $watchDog
     */
    public function delete(WatchDog $watchDog): void
    {
        $this->em->remove($watchDog);
        $this->em->flush();
    }
}
