<?php

namespace App\Command;

use App\Repository\IssueRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('app:issue:cleanup', 'Deletes old issues from the database')]
class IssueCleanupCommand extends Command
{
    public function __construct(
        private IssueRepository $issueRepository,
    )
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Dry run');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('dry-run')) {
            $io->note('Dry mode enabled');

            $count = $this->issueRepository->countOldRejected();
        } else {
            $count = $this->issueRepository->deleteOldRejected();
        }

        $io->success(sprintf('Deleted "%d" old issues.', $count));

        return Command::SUCCESS;
    }
}