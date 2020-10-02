<?php

declare(strict_types=1);

namespace App\Model\Url\Migration;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="url_migration_issue")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 */
class LegacyUrlMigrationIssue
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private int $id;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    private string $legacyUrl;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    private string $newUrl;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    private string $legacyType;

    /**
     * @ORM\Column(type="text", nullable=false)
     */
    private string $message;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTime $created;

    /**
     * @param string $legacyUrl
     * @param string $newUrl
     * @param string $legacyType
     * @param string $message
     */
    public function __construct(string $legacyUrl, string $newUrl, string $legacyType, string $message)
    {
        $this->legacyUrl = $legacyUrl;
        $this->newUrl = $newUrl;
        $this->legacyType = $legacyType;
        $this->message = $message;
        $this->created = new \DateTime();
    }
}
