<?php

declare(strict_types=1);

namespace App\Component\Transfer;

use App\Component\Transfer\Logger\TransferLoggerFactory;
use App\Model\Transfer\Issue\TransferIssueFacade;
use App\Model\Transfer\TransferFacade;
use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\Doctrine\SqlLoggerFacade;
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
     * @var \App\Model\Transfer\TransferFacade
     */
    private $transferFacade;

    /**
     * @var \App\Component\Transfer\Logger\TransferLoggerFactory
     */
    private $transferLoggerFactory;

    /**
     * @var \App\Component\Transfer\TransferConfig
     */
    private $transferConfig;

    /**
     * @var \App\Model\Transfer\Issue\TransferIssueFacade
     */
    private $transferIssueFacade;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\FrameworkBundle\Component\Doctrine\SqlLoggerFacade $sqlLoggerFacade
     * @param \Symfony\Component\Validator\Validator\ValidatorInterface $validator
     * @param \App\Model\Transfer\TransferFacade $transferFacade
     * @param \App\Component\Transfer\Logger\TransferLoggerFactory $transferLoggerFactory
     * @param \App\Component\Transfer\TransferConfig $transferConfig
     * @param \App\Model\Transfer\Issue\TransferIssueFacade $transferIssueFacade
     */
    public function __construct(
        EntityManagerInterface $em,
        SqlLoggerFacade $sqlLoggerFacade,
        ValidatorInterface $validator,
        TransferFacade $transferFacade,
        TransferLoggerFactory $transferLoggerFactory,
        TransferConfig $transferConfig,
        TransferIssueFacade $transferIssueFacade
    ) {
        $this->em = $em;
        $this->sqlLoggerFacade = $sqlLoggerFacade;
        $this->validator = $validator;
        $this->transferFacade = $transferFacade;
        $this->transferLoggerFactory = $transferLoggerFactory;
        $this->transferConfig = $transferConfig;
        $this->transferIssueFacade = $transferIssueFacade;
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
     * @return \App\Model\Transfer\TransferFacade
     */
    public function getTransferFacade(): TransferFacade
    {
        return $this->transferFacade;
    }

    /**
     * @return \App\Component\Transfer\Logger\TransferLoggerFactory
     */
    public function getTransferLoggerFactory(): TransferLoggerFactory
    {
        return $this->transferLoggerFactory;
    }

    /**
     * @return \App\Component\Transfer\TransferConfig
     */
    public function getTransferConfig(): TransferConfig
    {
        return $this->transferConfig;
    }

    /**
     * @return \App\Model\Transfer\Issue\TransferIssueFacade
     */
    public function getTransferIssueFacade(): TransferIssueFacade
    {
        return $this->transferIssueFacade;
    }
}
