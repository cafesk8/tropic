<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Parameter;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

class AdminSelectedParameter
{
    private const SESSION_SELECTED_PARAMETER = 'admin_selected_parameter_id';

    /**
     * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
     */
    private $session;

    /**
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     */
    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * @param int $parameterId
     */
    public function setSelectParameter(int $parameterId): void
    {
        $this->session->set(self::SESSION_SELECTED_PARAMETER, $parameterId);
    }

    /**
     * @return int
     */
    public function getSelectedParameter(): int
    {
        return $this->session->get(self::SESSION_SELECTED_PARAMETER);
    }
}
