<?php

declare(strict_types=1);

namespace App\Component\FlashMessage;

use Shopsys\FrameworkBundle\Component\FlashMessage\FlashMessage;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Twig\Environment;

/**
 * Copy pasted from @see \Shopsys\FrameworkBundle\Component\FlashMessage\FlashMessageTrait
 * so it is possible to add flash messages outside the controllers
 * @see https://github.com/shopsys/shopsys/issues/1813
 */
class FlashMessageSender
{
    /**
     * @var \Symfony\Component\HttpFoundation\Session\Session
     */
    private $session;

    /**
     * @var \Twig\Environment
     */
    private $twig;

    /**
     * @param \Twig\Environment $twig
     * @param \Symfony\Component\HttpFoundation\Session\Session $session
     */
    public function __construct(Environment $twig, SessionInterface $session)
    {
        $this->twig = $twig;
        $this->session = $session;
    }

    /**
     * @param string $template
     * @param array $parameters
     */
    public function addSuccessFlashTwig(string $template, array $parameters = []): void
    {
        $this->addSuccessFlash($this->renderStringTwigTemplate($template, $parameters));
    }

    /**
     * @param string $template
     * @param array $parameters
     */
    public function addErrorFlashTwig(string $template, array $parameters = []): void
    {
        $this->addErrorFlash($this->renderStringTwigTemplate($template, $parameters));
    }

    /**
     * @param string $template
     * @param array $parameters
     */
    public function addInfoFlashTwig(string $template, array $parameters = []): void
    {
        $this->addInfoFlash($this->renderStringTwigTemplate($template, $parameters));
    }

    /**
     * @param string $message
     */
    public function addErrorFlash(string $message): void
    {
        $this->addFlashMessage(FlashMessage::KEY_ERROR, $message);
    }

    /**
     * @param string $message
     */
    public function addInfoFlash(string $message): void
    {
        $this->addFlashMessage(FlashMessage::KEY_INFO, $message);
    }

    /**
     * @param string $message
     */
    public function addSuccessFlash(string $message): void
    {
        $this->addFlashMessage(FlashMessage::KEY_SUCCESS, $message);
    }

    /**
     * @param string $type
     * @param string $message
     */
    protected function addFlashMessage(string $type, string $message): void
    {
        $this->session->getFlashBag()->add($type, $message);
    }

    /**
     * @param string $template
     * @param array $parameters
     * @return string
     */
    protected function renderStringTwigTemplate(string $template, array $parameters = []): string
    {
        $twigTemplate = $this->twig->createTemplate($template);

        return $twigTemplate->render($parameters);
    }
}
