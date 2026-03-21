<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\CourseType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Course types from Komm! Hundeschule – Kursübersicht (https://www.komm-hundeschule.com/preise/).
 */
final class CourseTypeFixtures extends Fixture
{
    /** @var array{code: string, name: string}[] */
    private const COURSE_TYPES = [
        ['code' => 'JUHU', 'name' => 'Junghunde'],
        ['code' => 'MH', 'name' => 'Mensch & Hund'],
        ['code' => 'AGI', 'name' => 'Agility'],
        ['code' => 'APP', 'name' => 'Apportieren'],
        ['code' => 'CC', 'name' => 'Canicross'],
        ['code' => 'DS', 'name' => 'Dogscooter'],
        ['code' => 'DIA', 'name' => 'Duftidentifikationsarbeit'],
        ['code' => 'FSTS', 'name' => 'Flächen-, Trümmersuche'],
        ['code' => 'DF', 'name' => 'Dog-Frisbee'],
        ['code' => 'MT', 'name' => 'Mantrailing'],
        ['code' => 'RO', 'name' => 'Rally Obedience'],
        ['code' => 'TK', 'name' => 'Trickkurs'],
        ['code' => 'THS', 'name' => 'Turnierhundsport'],
    ];

    public function load(ObjectManager $manager): void
    {
        $repo = $manager->getRepository(CourseType::class);
        foreach (self::COURSE_TYPES as ['code' => $code, 'name' => $name]) {
            if ($repo->findOneBy(['code' => $code]) !== null) {
                continue;
            }
            $type = new CourseType();
            $type->setCode($code);
            $type->setName($name);
            $manager->persist($type);
        }
        $manager->flush();
    }
}
