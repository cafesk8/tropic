<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Transfer;

use DateTime;

class TransferConfig
{
    public const DATETIME_FORMAT = DateTime::RFC3339_EXTENDED;

    /**
     * @var string
     */
    private $host;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var bool
     */
    private $enabled;

    /**
     * @param string $host
     * @param string $username
     * @param string $password
     * @param bool $enabled
     */
    public function __construct(string $host, string $username, string $password, bool $enabled)
    {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->enabled = $enabled;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @return bool
     */
    public function areCredentialsFilled(): bool
    {
        return $this->enabled === true && $this->host != null && $this->username != null && $this->password != null;
    }
}
