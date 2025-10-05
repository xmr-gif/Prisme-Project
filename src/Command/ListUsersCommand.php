<?php

namespace App\Command;

use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:list-users',
    description: 'List all registered users',
)]
class ListUsersCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $users = $this->userRepository->findAll();

        if (empty($users)) {
            $io->warning('No users found in the database.');
            return Command::SUCCESS;
        }

        $io->title('Registered Users');

        $rows = [];
        foreach ($users as $user) {
            $rows[] = [
                $user->getId(),
                $user->getEmail(),
                $user->getFullName(),
                $user->getGoogleId() ? 'Yes' : 'No',
                $user->isVerified() ? 'Yes' : 'No',
                $user->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }

        $io->table(
            ['ID', 'Email', 'Full Name', 'Google', 'Verified', 'Created At'],
            $rows
        );

        $io->success(sprintf('Total users: %d', count($users)));

        return Command::SUCCESS;
    }
}
