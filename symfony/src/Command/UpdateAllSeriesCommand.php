<?php

namespace App\Command;

use App\Entity\Series;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;

#[AsCommand(
    name: 'app:update-all-series',
    description: 'Update all series from OMDB with the latest data',
)]
class UpdateAllSeriesCommand extends Command
{
    private static $OMDB_API_KEY = 'ae3a30a1';
    private $entityManager;
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this->setDescription('Update all series from OMDB with the latest data');
        $this->setHelp('This command allows you to update all series from OMDB with the latest data');

        $this->addArgument(
            'nb_series_updated',
            InputArgument::OPTIONAL,
            'Number of series to update, -1 = all series',
            '-1'
        );
        $this->addArgument('offset', InputArgument::OPTIONAL, 'Offset of series to update', '0');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $seriesRepository = $this->entityManager->getRepository(Series::class);
        $series = $seriesRepository->findAll();

        $offset = $input->getArgument('offset');
        $series = array_slice($series, $offset);

        $nbSeriesUpdated = $input->getArgument('nb_series_updated');
        if ($nbSeriesUpdated != -1) {
            $series = array_slice($series, 0, $nbSeriesUpdated);
        }
        $io->progressStart(count($series));
        foreach ($series as $serie) {
            if (!$seriesRepository->updateSeries(
                $serie->getImdb(),
                UpdateAllSeriesCommand::$OMDB_API_KEY
            )) {
                $io->error('Error updating serie SeriesId :' . $serie->getId() . ' IMDB: ' .
                $serie->getImdb() . ' Title: ' . $serie->getTitle());
                return Command::FAILURE;
            }
            
            $io->progressAdvance();
        }
        $io->progressFinish();

        $io->success('All series updated');

        return Command::SUCCESS;
    }
}
