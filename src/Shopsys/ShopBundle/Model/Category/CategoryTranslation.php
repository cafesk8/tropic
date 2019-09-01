<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Category;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Model\Category\CategoryTranslation as BaseCategoryTranslation;

/**
 * @ORM\Table(name="category_translations")
 * @ORM\Entity
 */
class CategoryTranslation extends BaseCategoryTranslation
{
    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $leftBannerText;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $rightBannerText;

    /**
     * @return string|null
     */
    public function getLeftBannerText(): ?string
    {
        return $this->leftBannerText;
    }

    /**
     * @param string|null $leftBannerText
     */
    public function setLeftBannerText(?string $leftBannerText): void
    {
        $this->leftBannerText = $leftBannerText;
    }

    /**
     * @return string|null
     */
    public function getRightBannerText(): ?string
    {
        return $this->rightBannerText;
    }

    /**
     * @param string|null $rightBannerText
     */
    public function setRightBannerText(?string $rightBannerText): void
    {
        $this->rightBannerText = $rightBannerText;
    }
}
