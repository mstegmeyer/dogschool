<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\ArgumentResolver;

use App\Controller\ArgumentResolver\CurrentCustomerOrUserValueResolver;
use App\Entity\Customer;
use App\Entity\User;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class CurrentCustomerOrUserValueResolverTest extends TestCase
{
    private function createResolver(?TokenInterface $token): CurrentCustomerOrUserValueResolver
    {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        return new CurrentCustomerOrUserValueResolver($tokenStorage);
    }

    private function argument(string $type): ArgumentMetadata
    {
        return new ArgumentMetadata('value', $type, false, false, null);
    }

    #[Test]
    public function returnsEmptyForUnrelatedType(): void
    {
        $resolver = $this->createResolver(null);
        $result = iterator_to_array($resolver->resolve(new Request(), $this->argument(\stdClass::class)));

        self::assertSame([], $result);
    }

    #[Test]
    public function throwsUnauthorizedWhenNoToken(): void
    {
        $resolver = $this->createResolver(null);

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Authentication required');

        iterator_to_array($resolver->resolve(new Request(), $this->argument(Customer::class)));
    }

    #[Test]
    public function throwsUnauthorizedWhenTokenHasNoUser(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);
        $resolver = $this->createResolver($token);

        $this->expectException(UnauthorizedHttpException::class);

        iterator_to_array($resolver->resolve(new Request(), $this->argument(Customer::class)));
    }

    #[Test]
    public function throwsUnauthorizedWhenAdminTriesToAccessCustomerEndpoint(): void
    {
        $user = new User();
        $user->setUsername('admin');
        $user->setFullName('Admin');
        $user->setPassword('x');

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $resolver = $this->createResolver($token);

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Customer authentication required');

        iterator_to_array($resolver->resolve(new Request(), $this->argument(Customer::class)));
    }

    #[Test]
    public function throwsUnauthorizedWhenCustomerTriesToAccessAdminEndpoint(): void
    {
        $customer = new Customer();
        $customer->setEmail('c@example.com');
        $customer->setName('C');
        $customer->setPassword('x');

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($customer);
        $resolver = $this->createResolver($token);

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Admin authentication required');

        iterator_to_array($resolver->resolve(new Request(), $this->argument(User::class)));
    }

    #[Test]
    public function yieldsCustomerWhenAuthenticatedAsCustomer(): void
    {
        $customer = new Customer();
        $customer->setEmail('c@example.com');
        $customer->setName('C');
        $customer->setPassword('x');

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($customer);
        $resolver = $this->createResolver($token);

        $result = iterator_to_array($resolver->resolve(new Request(), $this->argument(Customer::class)));

        self::assertCount(1, $result);
        self::assertSame($customer, $result[0]);
    }

    #[Test]
    public function yieldsUserWhenAuthenticatedAsAdmin(): void
    {
        $user = new User();
        $user->setUsername('admin');
        $user->setFullName('Admin');
        $user->setPassword('x');

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $resolver = $this->createResolver($token);

        $result = iterator_to_array($resolver->resolve(new Request(), $this->argument(User::class)));

        self::assertCount(1, $result);
        self::assertSame($user, $result[0]);
    }
}
