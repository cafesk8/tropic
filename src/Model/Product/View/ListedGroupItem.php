<?php

declare(strict_types=1);

namespace App\Model\Product\View;

use Shopsys\ReadModelBundle\Image\ImageView;

class ListedGroupItem
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    private $amount;

    /**
     * @var \Shopsys\ReadModelBundle\Image\ImageView|null
     */
    private $image;

    /**
     * @param string $name
     * @param int $amount
     * @param \Shopsys\ReadModelBundle\Image\ImageView|null $imageView
     */
    public function __construct(string $name, int $amount, ?ImageView $imageView)
    {
        $this->name = $name;
        $this->amount = $amount;
        $this->image = $imageView;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * @return \Shopsys\ReadModelBundle\Image\ImageView|null
     */
    public function getImage(): ?ImageView
    {
        return $this->image;
    }
}
