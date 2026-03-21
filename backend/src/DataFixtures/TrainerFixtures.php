<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Trainers (admin users) from Komm! Hundeschule – Trainer:innen (https://www.komm-hundeschule.com/).
 * Password is from env FIXTURE_ADMIN_PASSWORD or defaults to "change-me"; change after first login.
 */
final class TrainerFixtures extends Fixture implements DependentFixtureInterface
{
    /** @var array{username: string, fullName: string, phone: string}[] */
    private const TRAINERS = [
        ['username' => 'florian', 'fullName' => 'Florian', 'phone' => '01523 6628574'],
        ['username' => 'manuela', 'fullName' => 'Manuela', 'phone' => '01609 1989801'],
        ['username' => 'caro', 'fullName' => 'Caro', 'phone' => '01754 174670'],
        ['username' => 'lea', 'fullName' => 'Lea', 'phone' => '01516 7764591'],
    ];

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $rawPassword = $_ENV['FIXTURE_ADMIN_PASSWORD'] ?? null;
        $password = is_string($rawPassword) && $rawPassword !== '' ? $rawPassword : 'change-me';
        $repo = $manager->getRepository(User::class);

        foreach (self::TRAINERS as ['username' => $username, 'fullName' => $fullName, 'phone' => $phone]) {
            if ($repo->findOneBy(['username' => $username]) !== null) {
                continue;
            }
            $user = new User();
            $user->setUsername($username);
            $user->setFullName($fullName);
            $user->setPhone($phone);
            $user->setPassword($this->passwordHasher->hashPassword($user, $password));
            $manager->persist($user);
        }
        $manager->flush();
    }

    /** @return list<class-string<Fixture>> */
    public function getDependencies(): array
    {
        return [CourseTypeFixtures::class];
    }
}
