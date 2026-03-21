<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\CourseDateService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate-course-dates',
    description: 'Generate CourseDate rows for recurring courses N months into the future.',
)]
class GenerateCourseDatesCommand extends Command
{
    public function __construct(
        private readonly CourseDateService $courseDateService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('months', 'm', InputOption::VALUE_OPTIONAL, 'How many months ahead to generate', '3');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $monthsRaw = $input->getOption('months');
        $months = 3;
        if (is_string($monthsRaw) || is_int($monthsRaw)) {
            $parsed = (int) $monthsRaw;
            if ($parsed >= 1) {
                $months = $parsed;
            }
        }

        $from = new \DateTimeImmutable('today');
        $until = $from->modify("+{$months} months");

        $io->info(sprintf('Generating course dates from %s to %s ...', $from->format('Y-m-d'), $until->format('Y-m-d')));

        $created = $this->courseDateService->generateDates($from, $until);

        $io->success(sprintf('Created %d new course date(s).', $created));

        return Command::SUCCESS;
    }
}
