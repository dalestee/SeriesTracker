<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\Episode;
use App\Entity\Series;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Faker\Factory;
use Symfony\Component\Console\Helper\ProgressBar;

#[AsCommand(
    name: 'app:follow-view-series',
    description: 'Make users follow and view random series',
)]
class FollowViewSeriesCommand extends Command
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
                'nbSeriesMin',
                InputArgument::OPTIONAL,
                'Minimum number of series to follow for each user',
                0
            );

        $this
            ->addArgument(
                'nbSeriesMax',
                InputArgument::OPTIONAL,
                'Maximum number of series to follow for each user',
                10
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new \Symfony\Component\Console\Style\SymfonyStyle($input, $output);

        $nbSeriesMin = $input->getArgument('nbSeriesMin');
        $nbSeriesMax = $input->getArgument('nbSeriesMax');
        $users = $this->entityManager->getRepository(User::class)->findBy(['admin' => -1]);
        $series = $this->entityManager->getRepository(Series::class)->findAll();

        // Create a new progress bar (50 units)
        $progressBar = new ProgressBar($output, count($users));

        // Start and displays the progress bar
        $progressBar->start();

        while (!empty($users)) {
            shuffle($series);
            $user = array_pop($users);
            $nbSeries = $this->faker->numberBetween($nbSeriesMin, $nbSeriesMax);
            $seriesToFollow = array_slice($series, 0, $nbSeries);
            foreach ($seriesToFollow as $serie) {
                $episodesInfos = $this->entityManager->getRepository(Series::class)->episodeOfSeries($serie->getId());
                $episodesInfos = array_slice($episodesInfos, 0, $this->faker->numberBetween(0, count($episodesInfos)));
                $user->addSeries($serie);
                foreach ($episodesInfos as $episodeInfos) {
                    $episode = $this->entityManager->getRepository(Episode::class)->find($episodeInfos['episode_id']);
                    $user->addEpisode($episode);
                    $episode->addUser($user);
                    $this->entityManager->persist($episode);
                }
            }
            $this->entityManager->persist($user);
            if (count($users) % 100 == 0) {
                $this->entityManager->flush();
            }

            // Advance the progress bar by one "step"
            $progressBar->advance();
        }
        $this->entityManager->flush();
        // Ensure that the progress bar is at 100%
        $progressBar->finish();

        $io->success('Users follow and view series successfully');
        return Command::SUCCESS;
    }
}
