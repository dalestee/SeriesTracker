<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\Country;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-users',
    description: 'Create multiple users with realistic data',
)]
class CreateUserCommand extends Command
{
    private $entityManager;
    private $userPasswordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->userPasswordHasher = $userPasswordHasher;
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'nbUsers',
                InputArgument::OPTIONAL,
                'Number of users to create',
                10
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $faker = Factory::create();

        $nbUsers = $input->getArgument('nbUsers');

        // Récupérez tous les pays de la base de données
        $countries = $this->entityManager->getRepository(Country::class)->findAll();

        $io->title(sprintf('Creating %s users', $nbUsers));
        $io->progressStart($nbUsers);

        for ($i = 0; $i < $nbUsers; $i++) {
            $user = new User();
            $email = $faker->unique()->safeEmail;
            $uniqueEmail = str_replace('@', $i . '@', $email); // Ajoutez l'index à l'e-mail
            $user->setEmail($uniqueEmail);
            $user->setName($faker->name);
            $user->setPassword(
                $this->userPasswordHasher->hashPassword(
                    $user,
                    $uniqueEmail // Utilisez l'e-mail unique pour le mot de passe
                )
            );
            $user->setAdmin(-1);
            // Sélectionnez un pays au hasard parmi la liste
            $country = $faker->randomElement($countries);
            $user->setCountry($country);
            $user->setRegisterDate(new \DateTime());
            $this->entityManager->persist($user);
             // flush tout les 10 utilisateurs créers
            if (($i + 1) % 50 == 0) {
                $this->entityManager->flush();
            }
            $io->progressAdvance();
        }

        $this->entityManager->flush();
        $io->progressFinish();

        $io->success(sprintf('%s users have been successfully generated.', $nbUsers));

        return Command::SUCCESS;
    }
}
