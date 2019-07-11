<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Instagram;

class InstagramTemplateObject
{
    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $url;

    /**
     * @var string|null
     */
    private $link;

    /**
     * @param string $url
     * @param string $title
     * @param string|null $link
     */
    public function __construct(string $url, string $title, ?string $link = null)
    {
        $this->url = $url;
        $this->title = $title;
        $this->link = $link;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return string|null
     */
    public function getLink(): ?string
    {
        return $this->link;
    }
}
