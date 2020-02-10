<?php

declare(strict_types=1);

namespace App\Command\Migration;

use App\Command\Migration\Exception\PasswordsFileNotFoundException;
use App\Model\Customer\BushmanCustomPasswordEncoder;
use App\Model\Customer\User\CustomerUserFacade;
use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUser;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserPasswordFacade;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class MigrateCustomersPasswordsCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'shopsys:migrate:customer-passwords';

    private $rootDir;

    /**
     * @var \App\Model\Customer\User\CustomerUserFacade
     */
    private $customerUserFacade;

    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    protected $em;

    /**
     * @var \Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface
     */
    protected $encoderFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserPasswordFacade
     */
    protected $customerUserPasswordFacade;

    /**
     * @param string $rootDir
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \App\Model\Customer\User\CustomerUserFacade $customerUserFacade
     * @param \Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface $encoderFactory
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserPasswordFacade $customerUserPasswordFacade
     */
    public function __construct(
        string $rootDir,
        EntityManagerInterface $em,
        CustomerUserFacade $customerUserFacade,
        EncoderFactoryInterface $encoderFactory,
        CustomerUserPasswordFacade $customerUserPasswordFacade
    ) {
        parent::__construct();

        $this->em = $em;
        $this->rootDir = $rootDir;
        $this->customerUserFacade = $customerUserFacade;
        $this->encoderFactory = $encoderFactory;
        $this->customerUserPasswordFacade = $customerUserPasswordFacade;
    }

    protected function configure(): void
    {
        $this->setDescription('Run customers passwords migration from bushamn club json file');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('start password migration');

        $migratedCustomerDataFilePath = $this->askForFilePath($input, $output);
        $passwordsByEmail = $this->getPasswordsByEmail($migratedCustomerDataFilePath, $output);

        $allCustomers = $this->customerUserFacade->getAllUsers();
        $output->writeln('Count of users: ' . count($allCustomers));

        /** @var \App\Model\Customer\User\CustomerUser $customer */
        foreach ($allCustomers as $customer) {
            if (array_key_exists($customer->getEmail(), $passwordsByEmail) === false) {
                $output->writeln(sprintf('!!! Not password found for email %s', $customer->getEmail()));
                continue;
            }

            $this->changePasswordByMigration($customer, $passwordsByEmail[$customer->getEmail()]);
            $output->writeln(sprintf('Password for email %s was migrated.', $customer->getEmail()));
        }

        $this->em->flush();
        $output->writeln('Password migrations finished');

        return 0;
    }

    /**
     * @param string $migratedCustomerDataFilePath
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return mixed[]
     */
    private function getPasswordsByEmail(string $migratedCustomerDataFilePath, OutputInterface $output): array
    {
        $migratedCustomerDataJson = file_get_contents($this->rootDir . '/' . $migratedCustomerDataFilePath);
        $migratedCustomerData = json_decode($migratedCustomerDataJson, true);

        $migratedCustomerDataByEmail = [];
        foreach ($migratedCustomerData as $customerUserUpdateData) {
            if (array_key_exists('USR_Login', $customerUserUpdateData) === false || array_key_exists('USR_Password', $customerUserUpdateData) === false) {
                $output->writeln('!!! Bad customer data');
                continue;
            }

            $migratedCustomerDataByEmail[$customerUserUpdateData['USR_Login']] = $customerUserUpdateData['USR_Password'];
        }

        return $migratedCustomerDataByEmail;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return string
     */
    private function askForFilePath(InputInterface $input, OutputInterface $output): string
    {
        $questionHelper = $this->getHelper('question');
        $question = new Question('file path: ', 'migration-data/usr_users.json');
        $migratedCustomerDataFilePath = $questionHelper->ask($input, $output, $question);

        if ($migratedCustomerDataFilePath === null) {
            throw new PasswordsFileNotFoundException('File ' . $migratedCustomerDataFilePath . ' with password not found');
        }

        return $migratedCustomerDataFilePath;
    }

    /**
     * @param \App\Model\Customer\User\CustomerUser $customerUser
     * @param string $password
     */
    private function changePasswordByMigration(CustomerUser $customerUser, string $password): void
    {
        $encoder = $this->encoderFactory->getEncoder($this);

        if ($encoder instanceof BushmanCustomPasswordEncoder) {
            $passwordHash = $encoder->getHashOfMigratedPassword($password, null);
            $customerUser->setPasswordHash($passwordHash);
            return;
        }

        $this->customerUserPasswordFacade->changePassword($customerUser, $password);
    }
}
