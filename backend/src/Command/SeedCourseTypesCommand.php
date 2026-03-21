<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\CourseType;
use App\Repository\CourseTypeRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-course-types',
    description: 'Ensure course types from Kursübersicht (JUHU, MH, AGI, etc.) exist.',
)]
final class SeedCourseTypesCommand extends Command
{
    /** From https://www.komm-hundeschule.com/preise/ – Kursübersicht. */
    private const DEFAULTS = [
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

    public function __construct(
        private readonly CourseTypeRepository $courseTypeRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        foreach (self::DEFAULTS as ['code' => $code, 'name' => $name]) {
            if ($this->courseTypeRepository->findByCode($code) !== null) {
                continue;
            }
            $type = new CourseType();
            $type->setCode($code);
            $type->setName($name);
            $this->courseTypeRepository->save($type);
            $io->text(sprintf('Created course type: %s (%s)', $code, $name));
        }

        $io->success('Course types seeded.');

        return Command::SUCCESS;
    }
}
