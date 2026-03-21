<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Contract;
use App\Enum\ContractState;
use App\Enum\ContractType;
use App\Repository\ContractRepository;
use App\Service\CreditService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:grant-weekly-credits',
    description: 'Grant weekly course credits to customers with active perpetual contracts.',
)]
class GrantWeeklyCreditsCommand extends Command
{
    public function __construct(
        private readonly ContractRepository $contractRepository,
        private readonly CreditService $creditService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('week', 'w', InputOption::VALUE_OPTIONAL, 'ISO week to grant for (e.g. 2026-W12). Defaults to current week.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $weekOption = $input->getOption('week');
        $weekRef = is_string($weekOption) && $weekOption !== ''
            ? $weekOption
            : (new \DateTimeImmutable())->format('o-\WW');

        $io->info(sprintf('Granting weekly credits for week %s ...', $weekRef));

        $contracts = $this->contractRepository->findBy([
            'state' => ContractState::ACTIVE,
            'type' => ContractType::PERPETUAL,
        ]);

        $granted = 0;
        $skipped = 0;

        /** @var Contract $contract */
        foreach ($contracts as $contract) {
            if ($contract->getCoursesPerWeek() <= 0) {
                continue;
            }

            $now = new \DateTimeImmutable();
            $startDate = $contract->getStartDate();
            $endDate = $contract->getEndDate();
            if ($startDate !== null && $startDate > $now) {
                continue;
            }
            if ($endDate !== null && $endDate < $now) {
                continue;
            }

            $tx = $this->creditService->grantWeeklyCredits($contract, $weekRef);
            if ($tx !== null) {
                ++$granted;
                $io->text(sprintf(
                    '  +%d credits → %s (contract %s)',
                    $contract->getCoursesPerWeek(),
                    $contract->getCustomer()?->getName() ?? '?',
                    $contract->getId(),
                ));
            } else {
                ++$skipped;
            }
        }

        $io->success(sprintf('Granted credits for %d contract(s), skipped %d (already granted).', $granted, $skipped));

        return Command::SUCCESS;
    }
}
