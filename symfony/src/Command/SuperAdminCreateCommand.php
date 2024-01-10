<?php

namespace App\Command;

use App\Entity\User;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:new-super-admin',
    description: 'Change the role of a user to super admin, the user must exist',
)]
class SuperAdminCreateCommand extends Command
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('user', InputArgument::REQUIRED, 'User email')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $userEmail = $input->getArgument('user');

        if ($userEmail) {
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $userEmail]);

            if (!$user) {
                $io->error('User not found');
                return Command::FAILURE;
            }

            if ($user->isSuperAdmin()) {
                $io->warning('User already super admin');
                return Command::FAILURE;
            }

            $user->setAdmin(2);
            $this->entityManager->flush();

            $io->success("User changed ".$user->getUserIdentifier()." to super admin");
            return Command::SUCCESS;
        }
        else {
            $io->error('Need to specify a user');
            return Command::FAILURE;
        }
        
        
    }
}
