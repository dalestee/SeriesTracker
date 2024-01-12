<?php

namespace App\Command;

use App\Entity\Comment;
use App\Entity\User;
use App\Entity\Series;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Faker\Factory;
use App\Entity\Rating;


#[AsCommand(
    name: 'app:create-rating',
    description: 'Create random comments for users',
)]
class CreateRatingCommand extends Command
{
    private $entityManager;
    private $faker;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->faker = Factory::create();

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('nbComms', InputArgument::OPTIONAL, 'Number of comments to create', 10);
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $nbComms = $input->getArgument('nbComms');
        $users = $this->entityManager->getRepository(User::class)->findBy(['admin' => -1]);
        $series = $this->entityManager->getRepository(Series::class)->findAll();
    
        // Mélanger la liste des utilisateurs
        shuffle($users);
    
        $ratingsCreated = 0;
        while ($ratingsCreated < $nbComms && !empty($users)) {
            // Prendre un utilisateur de la liste
            $user = array_pop($users);
    
            // Trouver une série que l'utilisateur n'a pas encore notée
            foreach ($series as $serie) {
                $rating = $this->entityManager->getRepository(Rating::class)->findOneBy(['user' => $user, 'series' => $serie]);
                if (!$rating) {
                    // Créer une note pour la série
                    $rating = new Rating();
                    $rating->setUser($user);
                    $rating->setSeries($serie);
                    $rating->setDate(new \DateTime());
                    $rating->setValue($this->faker->numberBetween(0, 5));
                    $rating->setComment($this->faker->text);
                    $this->entityManager->persist($rating);
    
                    $ratingsCreated++;
                    if ($ratingsCreated >= $nbComms) {
                        break 2; // Sortir de la boucle while et de la boucle foreach
                    }
                }
            }
        }
    
        $this->entityManager->flush();
    
        $io->success(sprintf('Successfully created %d ratings.', $ratingsCreated));
    
        return Command::SUCCESS;
    }
}