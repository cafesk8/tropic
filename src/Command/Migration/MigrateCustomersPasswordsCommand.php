<?php

declare(strict_types=1);

namespace App\Command\Migration;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Model\Customer\CustomerPasswordFacade;
use Shopsys\FrameworkBundle\Model\Customer\User;
use App\Command\Migration\Exception\PasswordsFileNotFoundException;
use App\Model\Customer\BushmanCustomPasswordEncoder;
use App\Model\Customer\CustomerFacade;
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
     * @var \App\Model\Customer\CustomerFacade
     */
    private $customerFacade;

    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    protected $em;

    /**
     * @var \Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface
     */
    protected $encoderFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Customer\CustomerPasswordFacade
     */
    protected $customerPasswordFacade;

    /**
     * @param string $rootDir
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \App\Model\Customer\CustomerFacade $customerFacade
     * @param \Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface $encoderFactory
     * @param \Shopsys\FrameworkBundle\Model\Customer\CustomerPasswordFacade $customerPasswordFacade
     */
    public function __construct(
        string $rootDir,
        EntityManagerInterface $em,
        CustomerFacade $customerFacade,
        EncoderFactoryInterface $encoderFactory,
        CustomerPasswordFacade $customerPasswordFacade
    ) {
        parent::__construct();

        $this->em = $em;
        $this->rootDir = $rootDir;
        $this->customerFacade = $customerFacade;
        $this->encoderFactory = $encoderFactory;
        $this->customerPasswordFacade = $customerPasswordFacade;
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

        $allCustomers = $this->customerFacade->getAllUsers();
        $output->writeln('Count of users: ' . count($allCustomers));

        /** @var \App\Model\Customer\User $customer */
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
        foreach ($migratedCustomerData as $customerData) {
            if (array_key_exists('USR_Login', $customerData) === false || array_key_exists('USR_Password', $customerData) === false) {
                $output->writeln('!!! Bad customer data');
                continue;
            }

            $migratedCustomerDataByEmail[$customerData['USR_Login']] = $customerData['USR_Password'];
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
     * @param \App\Model\Customer\User $user
     * @param string $password
     */
    private function changePasswordByMigration(User $user, string $password): void
    {
        $encoder = $this->encoderFactory->getEncoder($this);

        if ($encoder instanceof BushmanCustomPasswordEncoder) {
            $passwordHash = $encoder->getHashOfMigratedPassword($password, null);
            $user->setPasswordHash($passwordHash);
            return;
        }

        $this->customerPasswordFacade->changePassword($user, $password);
    }
}
