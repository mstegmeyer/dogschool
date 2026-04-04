<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command;

use App\Command\CreateUserCommand;
use App\Entity\Customer;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class CreateUserCommandTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private UserPasswordHasherInterface&MockObject $passwordHasher;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
    }

    #[Test]
    public function itCreatesAnAdminUser(): void
    {
        $userRepository = $this->createMock(EntityRepository::class);
        $userRepository
            ->expects(self::once())
            ->method('findOneBy')
            ->with(['username' => 'alice'])
            ->willReturn(null);

        $this->em
            ->expects(self::once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($userRepository);

        $this->passwordHasher
            ->expects(self::once())
            ->method('hashPassword')
            ->with(
                self::callback(function (User $user): bool {
                    self::assertSame('alice', $user->getUsername());
                    self::assertSame('Alice Admin', $user->getFullName());
                    self::assertSame('+49 123', $user->getPhone());

                    return true;
                }),
                'secret',
            )
            ->willReturn('hashed-secret');

        $this->em
            ->expects(self::once())
            ->method('persist')
            ->with(self::callback(function (User $user): bool {
                self::assertSame('alice', $user->getUsername());
                self::assertSame('Alice Admin', $user->getFullName());
                self::assertSame('+49 123', $user->getPhone());
                self::assertSame('hashed-secret', $user->getPassword());

                return true;
            }));

        $this->em->expects(self::once())->method('flush');

        $tester = new CommandTester(new CreateUserCommand($this->em, $this->passwordHasher));
        $exitCode = $tester->execute([
            'type' => 'admin',
            'identifier' => 'alice',
            'password' => 'secret',
            '--name' => 'Alice Admin',
            '--phone' => '+49 123',
        ]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Admin user "alice" created.', $tester->getDisplay());
    }

    #[Test]
    public function itCreatesACustomerUsingTheIdentifierAsFallbackName(): void
    {
        $customerRepository = $this->createMock(EntityRepository::class);
        $customerRepository
            ->expects(self::once())
            ->method('findOneBy')
            ->with(['email' => 'customer@example.com'])
            ->willReturn(null);

        $this->em
            ->expects(self::once())
            ->method('getRepository')
            ->with(Customer::class)
            ->willReturn($customerRepository);

        $this->passwordHasher
            ->expects(self::once())
            ->method('hashPassword')
            ->with(
                self::callback(function (Customer $customer): bool {
                    self::assertSame('customer@example.com', $customer->getEmail());
                    self::assertSame('customer@example.com', $customer->getName());

                    return true;
                }),
                'secret',
            )
            ->willReturn('hashed-customer');

        $this->em
            ->expects(self::once())
            ->method('persist')
            ->with(self::callback(function (Customer $customer): bool {
                self::assertSame('customer@example.com', $customer->getEmail());
                self::assertSame('customer@example.com', $customer->getName());
                self::assertSame('hashed-customer', $customer->getPassword());

                return true;
            }));

        $this->em->expects(self::once())->method('flush');

        $tester = new CommandTester(new CreateUserCommand($this->em, $this->passwordHasher));
        $exitCode = $tester->execute([
            'type' => 'customer',
            'identifier' => 'customer@example.com',
            'password' => 'secret',
        ]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Customer "customer@example.com" created.', $tester->getDisplay());
    }

    #[Test]
    public function itRejectsExistingCustomers(): void
    {
        $existingCustomer = (new Customer())
            ->setName('Existing')
            ->setEmail('customer@example.com')
            ->setPassword('hashed');

        $customerRepository = $this->createMock(EntityRepository::class);
        $customerRepository
            ->expects(self::once())
            ->method('findOneBy')
            ->with(['email' => 'customer@example.com'])
            ->willReturn($existingCustomer);

        $this->em
            ->expects(self::once())
            ->method('getRepository')
            ->with(Customer::class)
            ->willReturn($customerRepository);

        $this->passwordHasher->expects(self::never())->method('hashPassword');
        $this->em->expects(self::never())->method('persist');
        $this->em->expects(self::never())->method('flush');

        $tester = new CommandTester(new CreateUserCommand($this->em, $this->passwordHasher));
        $exitCode = $tester->execute([
            'type' => 'customer',
            'identifier' => 'customer@example.com',
            'password' => 'secret',
        ]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('Customer with this email already exists.', $tester->getDisplay());
    }

    #[Test]
    public function itRejectsUnsupportedTypes(): void
    {
        $this->em->expects(self::never())->method('getRepository');
        $this->passwordHasher->expects(self::never())->method('hashPassword');
        $this->em->expects(self::never())->method('persist');
        $this->em->expects(self::never())->method('flush');

        $tester = new CommandTester(new CreateUserCommand($this->em, $this->passwordHasher));
        $exitCode = $tester->execute([
            'type' => 'trainer',
            'identifier' => 'alice',
            'password' => 'secret',
        ]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('Type must be "admin" or "customer".', $tester->getDisplay());
    }
}
