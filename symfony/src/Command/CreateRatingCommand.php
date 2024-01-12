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
            ->addArgument(
                'ecartType',
                InputArgument::OPTIONAL,
                'Standard deviation for the ratings',
                1
            );
        $this
            ->addArgument(
                'nbRatingsMin',
                InputArgument::OPTIONAL,
                'Minimum number of ratings to create for each series',
                10
            );

        $this
            ->addArgument(
                'nbRatingsMax',
                InputArgument::OPTIONAL,
                'Maximum number of ratings to create for each series',
                150
            );
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ecartType = $input->getArgument('ecartType');
        $io = new SymfonyStyle($input, $output);
        $nbRatingMin = $input->getArgument('nbRatingsMin');
        $nbRatingMax = $input->getArgument('nbRatingsMax');
        $users = $this->entityManager->getRepository(User::class)->findBy(['admin' => -1]);
        $series = $this->entityManager->getRepository(Series::class)->findAll();

        $totalCreatedRatings = 0;
        // Mélanger la liste des series
        shuffle($series);

        while (!empty($series)) {
            $serie = array_pop($series);

            $nbRatings = $this->faker->numberBetween($nbRatingMin, $nbRatingMax);
            $ratingsCreated = 0;

            $averageRatings = $this->faker->randomFloat(2, 0, 6);
            
            shuffle($users);

            foreach ($users as $key => $user) {
                $rating = $this->entityManager
                ->getRepository(Rating::class)
                ->findOneBy(['user' => $user, 'series' => $serie]);
                if (!$rating) {
                    $rating = new Rating();
                    $rating->setUser($user);
                    $rating->setSeries($serie);
                    $rating->setDate(new \DateTime());
                    $ratingValue = $this->randomNormal($averageRatings, $ecartType);
                    $ratingValue = max(0, min(5, $ratingValue));
                    $rating->setValue($ratingValue);
                    $rating->setComment($this->faker->text);
                    $this->entityManager->persist($rating);

                    $ratingsCreated++;
                    if ($ratingsCreated >= $nbRatings) {
                        break;
                    }
                }
            }
            $totalCreatedRatings += $ratingsCreated;
        }
        $this->entityManager->flush();


        

        $io->success(sprintf('Successfully created %d ratings.', $totalCreatedRatings));

        return Command::SUCCESS;
    }
    public function randomNormal($mean, $sd)
    {
        $x = mt_rand() / mt_getrandmax();
        $y = mt_rand() / mt_getrandmax();
        return sqrt(-2 * log($x)) * cos(2 * pi() * $y) * $sd + $mean;
    }
}
