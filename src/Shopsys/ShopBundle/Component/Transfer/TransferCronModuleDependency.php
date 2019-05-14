<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Transfer;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\Doctrine\SqlLoggerFacade;
use Shopsys\ShopBundle\Component\Transfer\Logger\TransferLoggerFactory;
use Shopsys\ShopBundle\Model\Transfer\TransferFacade;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TransferCronModuleDependency
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Doctrine\SqlLoggerFacade
     */
    private $sqlLoggerFacade;

    /**
     * @var \Symfony\Component\Validator\Validator\ValidatorInterface
     */
    private $validator;

    /**
     * @var \Shopsys\ShopBundle\Model\Transfer\TransferFacade
     */
    private $transferFacade;

    /**
     * @var \Shopsys\ShopBundle\Component\Transfer\Logger\TransferLoggerFactory
     */
    private $transferLoggerFactory;

    /**
     * @var \Shopsys\ShopBundle\Component\Transfer\TransferConfig
     */
    private $transferConfig;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\FrameworkBundle\Component\Doctrine\SqlLoggerFacade $sqlLoggerFacade
     * @param \Symfony\Component\Validator\Validator\ValidatorInterface $validator
     * @param \Shopsys\ShopBundle\Model\Transfer\TransferFacade $transferFacade
     * @param \Shopsys\ShopBundle\Component\Transfer\Logger\TransferLoggerFactory $transferLoggerFactory
     * @param \Shopsys\ShopBundle\Component\Transfer\TransferConfig $transferConfig
     */
    public function __construct(
        EntityManagerInterface $em,
        SqlLoggerFacade $sqlLoggerFacade,
        ValidatorInterface $validator,
        TransferFacade $transferFacade,
        TransferLoggerFactory $transferLoggerFactory,
        TransferConfig $transferConfig
    ) {
        $this->em = $em;
        $this->sqlLoggerFacade = $sqlLoggerFacade;
        $this->validator = $validator;
        $this->transferFacade = $transferFacade;
        $this->transferLoggerFactory = $transferLoggerFactory;
        $this->transferConfig = $transferConfig;
    }

    /**
     * @return \Doctrine\ORM\EntityManagerInterface
     */
    public function getEntityManager(): EntityManagerInterface
    {
        return $this->em;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Component\Doctrine\SqlLoggerFacade
     */
    public function getSqlLoggerFacade(): SqlLoggerFacade
    {
        return $this->sqlLoggerFacade;
    }

    /**
     * @return \Symfony\Component\Validator\Validator\ValidatorInterface
     */
    public function getValidator(): ValidatorInterface
    {
        return $this->validator;
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Transfer\TransferFacade
     */
    public function getTransferFacade(): TransferFacade
    {
        return $this->transferFacade;
    }

    /**
     * @return \Shopsys\ShopBundle\Component\Transfer\Logger\TransferLoggerFactory
     */
    public function getTransferLoggerFactory(): TransferLoggerFactory
    {
        return $this->transferLoggerFactory;
    }

    /**
     * @return \Shopsys\ShopBundle\Component\Transfer\TransferConfig
     */
    public function getTransferConfig(): TransferConfig
    {
        return $this->transferConfig;
    }
}
