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
class  FollowViewSeriesCommand extends Command
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
        $nbSeriesMin = $input->getArgument('nbSeriesMin');
        $nbSeriesMax = $input->getArgument('nbSeriesMax');
        $users = $this->entityManager->getRepository(User::class)->findBy(['admin' => -1]);
        $series = $this->entityManager->getRepository(Series::class)->findAll();
        $counter = 0;

        shuffle($series);

        // Create a new progress bar (50 units)
        $progressBar = new ProgressBar($output, count($users));

        // Start and displays the progress bar
        $progressBar->start();

        while (!empty($users)) {
            $user = array_pop($users);
            $nbSeries = $this->faker->numberBetween($nbSeriesMin, $nbSeriesMax);
            $firstIndex = $this->faker->numberBetween(0, count($series) - $nbSeries);
            $seriesToFollow = array_slice($series, $firstIndex, $nbSeries);
            foreach ($seriesToFollow as $serie) {
                $episodes = $this->entityManager->getRepository(Series::class)->episodeOfSeries($serie->getId());
                $episodes = array_slice($episodes, 0, $this->faker->numberBetween(0, count($episodes)));
                $user->addSeries($serie);
                foreach ($episodes as $episode) {
                    $user->addEpisode($this->entityManager->getRepository(Episode::class)->find($episode['episode_id']));
                }
            }
            $this->entityManager->persist($user);
            $output->writeln(count($users) . ' users left');
            if (count($users) % 100 == 0) {
                $this->entityManager->flush();
            }

            // Advance the progress bar by one "step"
            $progressBar->advance();
        }

        // Ensure that the progress bar is at 100%
        $progressBar->finish();
        $output->writeln('');
        return Command::SUCCESS;
    }
}

