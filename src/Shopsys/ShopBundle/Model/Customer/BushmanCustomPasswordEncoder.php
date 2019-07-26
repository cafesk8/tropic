<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Customer;

use Symfony\Component\Security\Core\Encoder\BCryptPasswordEncoder;

class BushmanCustomPasswordEncoder extends BCryptPasswordEncoder
{
    /**
     * @param string $raw
     * @param string $salt
     * @return string
     */
    public function encodePassword($raw, $salt): string
    {
        return parent::encodePassword($this->getOldPasswordHash($raw), $salt);
    }

    /**
     * @param string $raw
     * @param string $salt
     * @return string
     */
    public function getHashOfMigratedPassword($raw, $salt): string
    {
        return parent::encodePassword($raw, $salt);
    }

    /**
     * @param string $encoded
     * @param string $raw
     * @param string $salt
     * @return bool
     */
    public function isPasswordValid($encoded, $raw, $salt): bool
    {
        return !$this->isPasswordTooLong($raw) && password_verify($this->getOldPasswordHash($raw), $encoded);
    }

    /**
     * @param string|null $raw
     * @return string
     */
    private function getOldPasswordHash(?string $raw): string
    {
        if ($raw === null) {
            $raw = $this->generateRandomString();
        }
        return md5($raw . str_repeat('*random salt*', 10));
    }

    /**
     * @param int $length
     * @return string
     */
    private function generateRandomString(int $length = 32): string
    {
        return substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes($length))), 0, $length);
    }
}
