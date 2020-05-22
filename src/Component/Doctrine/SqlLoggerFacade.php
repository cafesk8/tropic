<?php

declare(strict_types=1);

namespace App\Component\Doctrine;

use Shopsys\FrameworkBundle\Component\Doctrine\SqlLoggerFacade as BaseSqlLoggerFacade;

class SqlLoggerFacade extends BaseSqlLoggerFacade
{
    public function temporarilyDisableLogging()
    {
        if ($this->isLoggerTemporarilyDisabled) {
            return;
        }
        $this->sqlLogger = $this->em->getConnection()->getConfiguration()->getSQLLogger();
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);
        $this->isLoggerTemporarilyDisabled = true;
    }

    public function reenableLogging()
    {
        if (!$this->isLoggerTemporarilyDisabled) {
            return;
        }
        $this->em->getConnection()->getConfiguration()->setSQLLogger($this->sqlLogger);
        $this->sqlLogger = null;
        $this->isLoggerTemporarilyDisabled = false;
    }
}
