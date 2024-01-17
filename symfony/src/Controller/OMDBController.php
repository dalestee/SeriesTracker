<?php

namespace App\Controller;

use App\Entity\Actor;
use App\Entity\Series;
use App\Entity\Genre;
use App\Entity\Season;
use App\Entity\Country;
use App\Entity\Episode;
use DoctrineExtensions\Query\Postgresql\Year;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/OMDB')]
class OMDBController extends AbstractController
{
    #[Route('/', name: 'app_admin_series_index')]
    public function index(): Response
    {
        return $this->render('omdb/index.html.twig');
    }

    
    #[Route('/series', name: 'app_admin_series', methods: ['GET'])]
    public function series(EntityManagerInterface $entityManager, Request $request): Response
    {
        $name = $request->query->get('name', 'default');
        $imdb = $request->query->get('imdb');

        $client = HttpClient::create();

        $query = [
            'apikey' => '5140c72f',  
            'type' => 'series'
        ];

        if ($imdb) {
            $query['i'] = $imdb;
        } else {
            $query['s'] = $name;
        }

        $response = $client->request('GET', 'http://www.omdbapi.com/', ['query' => $query]);

        $serieImdb = $imdb;
        $existSerie = false;

        if ($imdb){
            $existingSeries = $entityManager->getRepository(Series::class)->findOneBy(['imdb' => $imdb]);
            if ($existingSeries) {
                $existSerie = true;
            }
        }

        $series = json_decode($response->getContent(), true);

        return $this->render('omdb/index.html.twig', [
            'series' => $series,
            'serieImdb' => $serieImdb,
            'existSerie' => $existSerie
        ]);
    }

    #[Route ('/series/import/{imdb}', name: 'app_admin_series_import', methods: ['GET'])]
    public function import(EntityManagerInterface $entityManager, Request $request, $imdb): Response
    {
        $client = HttpClient::create();

        $query = [
            'apikey' => '5140c72f', 
            'type' => 'series',
            'i' => $imdb
        ];

        $response = $client->request('GET', 'http://www.omdbapi.com/', ['query' => $query]);

        $series = json_decode($response->getContent(), true);
        $newSeries = new Series();
        $newSeries->setTitle($series['Title']);
        $newSeries->setImdb($series['imdbID']);
        $newSeries->setPoster($series['Poster']);
        $newSeries->setPlot($series['Plot']);
        $newSeries->setDirector($series['Director']);
        $newSeries->setAwards($series['Awards']);
        if ($series['Year']) {
            $years = explode('–', $series['Year']);
            $newSeries->setYearStart($years[0]);
            if (!empty($years[1])) {
                $newSeries->setYearEnd($years[1]);
            } 
        }
        if($series['Genre']) {
            $genres_imdb = explode(', ', $series['Genre']);
            foreach ($genres_imdb as $genre_imdb) {
                $genre = $entityManager->getRepository(Genre::class)->findOneBy(['name' => $genre_imdb]);
                if (!$genre) {
                    $genre = new Genre();
                    $genre->setName($genre_imdb);
                    $entityManager->persist($genre);
                }
                $newSeries->addGenre($genre);
            }
        }
        if ($series['Actors']){
            $actorNames = explode(', ', $series['Actors']);
            foreach ($actorNames as $actorName) {
                $actor = $entityManager->getRepository(Actor::class)->findOneBy(['name' => $actorName]);
                if (!$actor) {
                    $actor = new Actor();
                    $actor->setName($actorName);
                    $entityManager->persist($actor);
                }
                $newSeries->addActor($actor);
            }
        }
        if ($series['Country']) {
            $countryNames = explode(', ', $series['Country']);
            foreach ($countryNames as $countryName) {
                $country = $entityManager->getRepository(Country::class)->findOneBy(['name' => $countryName]);
                if (!$country) {
                    $country = new Country();
                    $country->setName($countryName);
                    $entityManager->persist($country);
                }
                $newSeries->addCountry($country);
            }
        }
        $seriesDetails = file_get_contents('http://www.omdbapi.com/?i=' . $imdb . '&apikey=' . '5140c72f');
        $seriesDetails = json_decode($seriesDetails, true);

        
        $totalSeasons = $seriesDetails['totalSeasons'];

        
        for ($seasonNumber = 1; $seasonNumber <= $totalSeasons; $seasonNumber++) {
            $seasonDetails = file_get_contents('http://www.omdbapi.com/?i=' . $imdb . '&season=' . $seasonNumber . '&apikey=' . '5140c72f');
            $seasonDetails = json_decode($seasonDetails, true);
        
            if (!is_array($seasonDetails)) {
                // Handle the error here, e.g. continue to the next iteration of the loop
                continue;
            }
        
            $season = new Season();
            $season->setNumber($seasonNumber);
        
            if (array_key_exists('Episodes', $seasonDetails)) {
                foreach ($seasonDetails['Episodes'] as $episodeDetails) {
                    $episode = new Episode();
                    $episode->setTitle($episodeDetails['Title']);
                    $episode->setNumber($episodeDetails['Episode']);
                    $episode->setImdb($episodeDetails['imdbID']);
                    $episode->setImdbRating(floatval($episodeDetails['imdbRating']));
                    if ($episodeDetails['Released'] !== 'N/A') {
                        $episode->setDate(new \DateTime($episodeDetails['Released']));
                    } else {
                        $episode->setDate(null);
                    }
                    $episode->setSeason($season);
                    $entityManager->persist($episode);
            
                    $season->addEpisode($episode);
                }
            }
        
            $newSeries->addSeason($season);
            $entityManager->persist($season);
        }

        $entityManager->persist($newSeries);
        $entityManager->flush();

        return $this->redirectToRoute('app_admin_series_index');
    }
}
