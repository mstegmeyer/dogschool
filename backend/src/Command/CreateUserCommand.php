<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Customer;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-user',
    description: 'Create an admin user or a customer (for development/setup).',
)]
final class CreateUserCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('type', InputArgument::REQUIRED, 'Type: admin or customer')
            ->addArgument('identifier', InputArgument::REQUIRED, 'Email (customer) or username (admin)')
            ->addArgument('password', InputArgument::REQUIRED, 'Password')
            ->addOption('name', null, InputOption::VALUE_OPTIONAL, 'Full name (admin) or customer name', '')
            ->addOption('phone', null, InputOption::VALUE_OPTIONAL, 'Phone number (admin only)', '');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $typeArg = $input->getArgument('type');
        $identifierArg = $input->getArgument('identifier');
        $passwordArg = $input->getArgument('password');
        if (!is_string($typeArg) || !is_string($identifierArg) || !is_string($passwordArg)) {
            $io->error('Invalid arguments.');

            return Command::FAILURE;
        }

        $nameOption = $input->getOption('name');
        $name = (is_string($nameOption) && $nameOption !== '') ? $nameOption : $identifierArg;

        $phoneOption = $input->getOption('phone');
        $phone = (is_string($phoneOption) && $phoneOption !== '') ? $phoneOption : null;

        if ($typeArg === 'admin') {
            $existing = $this->em->getRepository(User::class)->findOneBy(['username' => $identifierArg]);
            if ($existing) {
                $io->error('Admin user with this username already exists.');

                return Command::FAILURE;
            }
            $user = new User();
            $user->setUsername($identifierArg);
            $user->setFullName($name);
            if ($phone !== null) {
                $user->setPhone($phone);
            }
            $user->setPassword($this->passwordHasher->hashPassword($user, $passwordArg));
            $this->em->persist($user);
            $this->em->flush();
            $io->success(sprintf('Admin user "%s" created.', $identifierArg));

            return Command::SUCCESS;
        }

        if ($typeArg === 'customer') {
            $existing = $this->em->getRepository(Customer::class)->findOneBy(['email' => $identifierArg]);
            if ($existing) {
                $io->error('Customer with this email already exists.');

                return Command::FAILURE;
            }
            $customer = new Customer();
            $customer->setEmail($identifierArg);
            $customer->setName($name);
            $customer->setPassword($this->passwordHasher->hashPassword($customer, $passwordArg));
            $this->em->persist($customer);
            $this->em->flush();
            $io->success(sprintf('Customer "%s" created.', $identifierArg));

            return Command::SUCCESS;
        }

        $io->error('Type must be "admin" or "customer".');

        return Command::FAILURE;
    }
}
