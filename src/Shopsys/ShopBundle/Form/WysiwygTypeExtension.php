<?php

declare(strict_types = 1);

namespace Shopsys\ShopBundle\Form;

use Shopsys\FrameworkBundle\Component\Css\CssFacade;
use Shopsys\FrameworkBundle\Form\WysiwygTypeExtension as BaseWysiwygTypeExtension;
use Shopsys\FrameworkBundle\Model\Localization\Localization;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WysiwygTypeExtension extends BaseWysiwygTypeExtension
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\Css\CssFacade
     */
    private $cssFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Localization\Localization
     */
    private $localization;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Css\CssFacade $cssFacade
     * @param \Shopsys\FrameworkBundle\Model\Localization\Localization $localization
     */
    public function __construct(
        CssFacade $cssFacade,
        Localization $localization
    ) {
        parent::__construct($cssFacade, $localization);

        $this->cssFacade = $cssFacade;
        $this->localization = $localization;
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $cssVersion = $this->cssFacade->getCssVersion();

        $resolver->setDefaults([
            'config' => [
                'contentsCss' => [
                    'assets/admin/styles/wysiwyg_' . $cssVersion . '.css',
                ],
                'language' => $this->localization->getLocale(),
                'format_tags' => self::ALLOWED_FORMAT_TAGS,
                'bodyClass' => 'in-user-text',
            ],
        ]);
    }
}
