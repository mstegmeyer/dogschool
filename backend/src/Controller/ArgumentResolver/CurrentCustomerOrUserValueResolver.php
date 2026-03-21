<?php

declare(strict_types=1);

namespace App\Controller\ArgumentResolver;

use App\Entity\Customer;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Injects the logged-in Customer or User into controller arguments.
 * Performs the auth check: throws 401 if not authenticated or if the
 * authenticated identity does not match the requested type (e.g. Customer vs User).
 */
final class CurrentCustomerOrUserValueResolver implements ValueResolverInterface
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
    ) {
    }

    /**
     * @return iterable<int, Customer|User>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $type = $argument->getType();
        if ($type !== Customer::class && $type !== User::class) {
            return [];
        }

        $token = $this->tokenStorage->getToken();
        $identity = $token?->getUser();

        if ($identity === null) {
            throw new UnauthorizedHttpException('Bearer', 'Authentication required.');
        }

        if ($type === Customer::class) {
            if (!$identity instanceof Customer) {
                throw new UnauthorizedHttpException('Bearer', 'Customer authentication required.');
            }

            yield $identity;

            return;
        }

        if (!$identity instanceof User) {
            throw new UnauthorizedHttpException('Bearer', 'Admin authentication required.');
        }

        yield $identity;
    }
}
