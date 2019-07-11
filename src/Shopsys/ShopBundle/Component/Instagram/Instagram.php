<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Instagram;

use Vinkla\Instagram\Instagram as VinkaInstagram;

class Instagram
{
    /**
     * @var mixed[]
     */
    private $config;

    /**
     * @param mixed[] $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @param string $locale
     * @return \Shopsys\ShopBundle\Component\Instagram\InstagramTemplateObject[]
     */
    public function getInstagramTemplateObjects(string $locale): array
    {
        /** @var \Vinkla\Instagram\Instagram */
        $instagramClient = new VinkaInstagram($this->config[$locale]['accessToken']);
        $instagramTemplateObjects = [];

        foreach ($instagramClient->media(['count' => $this->config['limit'] + 1]) as $instagramObject) {
            $instagramObjectImage = $this->getInstagramPostImage($instagramObject);

            if ($instagramObjectImage !== null) {
                $instagramTemplateObjects[] = new InstagramTemplateObject(
                    $instagramObjectImage,
                    $this->getInstagramPostTitle($instagramObject),
                    $this->getInstagramPostLink($instagramObject)
                );
            }
        }

        return $instagramTemplateObjects;
    }

    /**
     * @param \stdClass $instagramObject
     * @return string
     */
    private function getInstagramPostTitle(\stdClass $instagramObject): string
    {
        $instagramObjectTitle = '';
        if (isset($instagramObject->caption) === true && isset($instagramObject->caption->text) === true) {
            $instagramObjectTitle = $instagramObject->caption->text;
        }

        return $instagramObjectTitle;
    }

    /**
     * @param \stdClass $instagramObject
     * @return string|null
     */
    private function getInstagramPostLink(\stdClass $instagramObject): ?string
    {
        $instagramobjectLink = null;
        if (isset($instagramObject->link) === true) {
            $instagramobjectLink = $instagramObject->link;
        }

        return $instagramobjectLink;
    }

    /**
     * @param \stdClass $instagramObject
     * @return string|null
     */
    private function getInstagramPostImage(\stdClass $instagramObject): ?string
    {
        $instagramObjectImage = null;
        $quality = $this->config['quality'];

        if (isset($instagramObject->images) === true && isset($instagramObject->images->$quality) === true && isset($instagramObject->images->$quality->url) === true) {
            $instagramObjectImage = $instagramObject->images->$quality->url;
        }

        return $instagramObjectImage;
    }

    /**
     * @param string $locale
     * @return string
     */
    public function getInstagramLink(string $locale): string
    {
        return $this->config[$locale]['link'];
    }
}
