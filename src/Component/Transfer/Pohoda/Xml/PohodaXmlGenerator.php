<?php

declare(strict_types=1);

namespace App\Component\Transfer\Pohoda\Xml;

use Twig\Environment;

class PohodaXmlGenerator
{
    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * @param \Twig\Environment $twig
     */
    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * @param string $templateFilePath
     * @param array $data
     * @return string
     */
    public function generateXmlRequest(string $templateFilePath, array $data): string
    {
        $template = $this->twig->load($templateFilePath);

        $xmlData = $template->renderBlock('main_content', $data);

        return $this->prepareEncodedData($xmlData);
    }

    /**
     * @param string $data
     * @return string
     */
    public function prepareEncodedData(string $data): string
    {
        return (string)iconv('UTF-8', 'WINDOWS-1250', $data);
    }
}
