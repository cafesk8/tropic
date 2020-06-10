<?php

declare(strict_types=1);

namespace App\Component\WatchDog;

use App\Component\Domain\DomainHelper;
use App\Component\Setting\Setting;
use App\Model\WatchDog\WatchDog;
use Shopsys\FrameworkBundle\Component\Router\DomainRouterFactory;
use Shopsys\FrameworkBundle\Model\Mail\MailTemplate;
use Shopsys\FrameworkBundle\Model\Mail\MessageData;
use Shopsys\FrameworkBundle\Model\Mail\MessageFactoryInterface;
use Shopsys\FrameworkBundle\Model\Mail\Setting\MailSetting;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class WatchDogMail implements MessageFactoryInterface
{
    public const MAIL_TEMPLATE_WATCH_DOG = 'watch_dog';

    public const VARIABLE_PRODUCT_NAME = '{product_name}';
    public const VARIABLE_PRODUCT_URL = '{product_url}';

    /**
     * @var \App\Component\Setting\Setting
     */
    private $setting;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Router\DomainRouterFactory
     */
    private $domainRouterFactory;

    /**
     * @param \App\Component\Setting\Setting $setting
     * @param \Shopsys\FrameworkBundle\Component\Router\DomainRouterFactory $domainRouterFactory
     */
    public function __construct(Setting $setting, DomainRouterFactory $domainRouterFactory)
    {
        $this->setting = $setting;
        $this->domainRouterFactory = $domainRouterFactory;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Mail\MailTemplate $template
     * @param \App\Model\WatchDog\WatchDog $watchDog
     * @return \Shopsys\FrameworkBundle\Model\Mail\MessageData|void
     */
    public function createMessage(MailTemplate $template, $watchDog)
    {
        return new MessageData(
            $watchDog->getEmail(),
            $template->getBccEmail(),
            $template->getBody(),
            $template->getSubject(),
            $this->setting->getForDomain(MailSetting::MAIN_ADMIN_MAIL, $watchDog->getPricingGroup()->getDomainId()),
            $this->setting->getForDomain(MailSetting::MAIN_ADMIN_MAIL_NAME, $watchDog->getPricingGroup()->getDomainId()),
            $this->getBodyVariablesReplacements($watchDog)
        );
    }

    /**
     * @param \App\Model\WatchDog\WatchDog $watchDog
     * @return array
     */
    private function getBodyVariablesReplacements(WatchDog $watchDog): array
    {
        $domainId = $watchDog->getPricingGroup()->getDomainId();
        $router = $this->domainRouterFactory->getRouter($domainId);

        return [
            self::VARIABLE_PRODUCT_NAME => $watchDog->getProduct()->getName(DomainHelper::DOMAIN_ID_TO_LOCALE[$domainId]),
            self::VARIABLE_PRODUCT_URL => $router->generate('front_product_detail', ['id' => $watchDog->getProduct()->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
        ];
    }
}
