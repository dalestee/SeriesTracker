<?php

namespace App\Repository;

use App\Entity\Series;
use App\Entity\User;
use App\Entity\Actor;
use App\Entity\Country;
use App\Entity\Episode;
use App\Entity\Genre;
use App\Entity\Season;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use App\Entity\SeriesSearch;

/**
 * @extends ServiceEntityRepository<Series>
 *
 * @method Series|null find($id, $lockMode = null, $lockVersion = null)
 * @method Series|null findOneBy(array $criteria, array $orderBy = null)
 * @method Series[]    findAll()
 * @method Series[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SeriesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Series::class);
    }

    public function queryFindRatingFromSeries(Series $series)
    {
        return $this->createQueryBuilder('s')
            ->select('r')
            ->from('App:Rating', 'r')
            ->where('r.series = :series')
            ->orderBy('r.date', 'DESC')
            ->setParameter('series', $series)
            ->getQuery();
    }

    public function queryRandom(int $seed)
    {
        $queryBuilder = $this->createQueryBuilder('p');
        $queryBuilder->orderBy('RAND(' . $seed . ')');
        return $queryBuilder;
    }

    public function queryVisionage(int $userId, array $arraySeriesId, int $seed = 0)
    {
        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addScalarResult('percentage_seen', 'percentage_seen');
        $sql = "SELECT ROUND( IFNULL(seen_episodes, 0) * 100.0 / IFNULL(total_episodes, 1), 2) AS percentage_seen
                FROM series
                LEFT JOIN (
                    SELECT S.series_id, COUNT(*) AS total_episodes
                    FROM season S
                    INNER JOIN episode E ON E.season_id = S.id
                    GROUP BY S.series_id
                ) total ON series.id = total.series_id
                LEFT JOIN (
                    SELECT S.series_id, COUNT(*) AS seen_episodes
                    FROM user_episode UE
                    INNER JOIN episode E ON UE.episode_id = E.id
                    INNER JOIN season S ON E.season_id = S.id
                    GROUP BY S.series_id
                ) seen ON series.id = seen.series_id
                WHERE series.id IN (:arraySeriesId)";

        if ($seed) {
            $sql = $sql . "ORDER BY RAND(:seed)";
        }

        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        $query->setParameter('userId', $userId);
        $query->setParameter('arraySeriesId', $arraySeriesId);

        if ($seed) {
            $query->setParameter('seed', $seed);
        }

        return $query->getResult();
    }

    public function querySeriesSuiviesTrieParVisionnage(int $userId, array $arraySeriesId = [])
    {
        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata('App\Entity\Series', 's');
        $rsm->addScalarResult('percentage_seen', 'percentage_seen');

        $sql = "
            SELECT series.*, 
                   ROUND(IFNULL(seen_episodes, 0) * 100.0 / IFNULL(total_episodes, 1), 2) AS percentage_seen
            FROM series
            LEFT JOIN (
                SELECT S.series_id, COUNT(*) AS total_episodes
                FROM episode E
                INNER JOIN season S ON E.season_id = S.id
                GROUP BY S.series_id
            ) total ON series.id = total.series_id
            LEFT JOIN (
                SELECT S.series_id, COUNT(*) AS seen_episodes
                FROM user_episode UE
                INNER JOIN episode E ON UE.episode_id = E.id
                INNER JOIN season S ON E.season_id = S.id
                WHERE UE.user_id = :userId
                GROUP BY S.series_id
            ) seen ON series.id = seen.series_id
            INNER JOIN user_series US ON series.id = US.series_id
            WHERE US.user_id = :userId";

        if ($arraySeriesId) {
            $sql =  $sql . " AND series.id IN (:arraySeriesId)";
        }
        $sql = $sql . " ORDER BY percentage_seen DESC";

        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        $query->setParameter('userId', $userId);
        if ($arraySeriesId) {
            $query->setParameter('arraySeriesId', $arraySeriesId);
        }

        $ormQuery = $query;

        return $ormQuery->getResult();
    }
    public function findByCriteria(array $criteria, $search)
    {
        $qb = $this->buildQuerryfindByCriteria($criteria, $search);
        $query = $qb->getQuery();

        $count = count($query->getResult());

        $query->setHint('knp_paginator.count', $count);

        return $query;
    }

    public function buildQuerryfindByCriteria(array $criteria, $search)
    {
        $qb = $this->createQueryBuilder('s');

        if (!empty($criteria['genre'])) {
            $qb->leftJoin('s.genre', 'g')
                ->andWhere('g.name IN (:genres)')
                ->groupBy('s.id')
                ->having('COUNT(s.id) = :count')
                ->setParameters([
                    'genres' => $criteria['genre'],
                    'count' => count($criteria['genre'])
                ]);
        }

        if (!empty($criteria['startDate'])) {
            $qb->andWhere('s.yearStart = :startDate')
                ->setParameter('startDate', $criteria['startDate']);
        }

        if (!empty($criteria['endDate'])) {
            $qb->andWhere('s.yearEnd = :endDate')
                ->setParameter('endDate', $criteria['endDate']);
        }
        if (!empty($search)) {
            $qb->andWhere('s.title LIKE :search OR s.plot LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }
        if (!empty($criteria['ratings'])) {
            $sub = $this->createQueryBuilder('s2')
                ->select('AVG(r.value)')
                ->leftJoin('s2.ratings', 'r')
                ->where('s2.id = s.id');
            $qb->addSelect('(' . $sub->getDQL() . ') as HIDDEN avgRating')
                ->orderBy('avgRating', $criteria['ratings']);
        }
        return $qb;
    }

    public function findByCriteriaFollow(User $user, array $criteria, $search)
    {

        $qb = $this->buildQuerryfindByCriteria($criteria, $search);
        $qb->leftJoin('s.user', 'u')
            ->andWhere('u.id = :userId')
            ->setParameter('userId', $user->getId());

        $query = $qb->getQuery();
        $count = count($query->getResult());

        $query->setHint('knp_paginator.count', $count);

        return $qb;
    }

    public function findUniqueGenres()
    {
        $qb = $this->createQueryBuilder('s')
            ->select('g.name')
            ->distinct()
            ->leftJoin('s.genre', 'g');

        return $qb;
    }


    public function updateSeries($imdb, $imdb_api_key): bool
    {
        $entityManager = $this->getEntityManager();
        $series = $this->findOneBy(['imdb' => $imdb]);

        if (!$series) {
            return false;
        }

        $newData = file_get_contents('http://www.omdbapi.com/?i=' . $imdb . '&apikey=' . $imdb_api_key);
        $newData = json_decode($newData, true);


        $series->setTitle($newData['Title']);
        $series->setPoster($newData['Poster']);
        $series->setPlot($newData['Plot']);
        $series->setDirector($newData['Director']);
        $series->setAwards($newData['Awards']);
        if ($newData['Year']) {
            $years = explode('–', $newData['Year']);
            $series->setYearStart($years[0]);
            if (!empty($years[1])) {
                $series->setYearEnd($years[1]);
            }
        }
        if ($newData['Genre']) {
            $genres_imdb = explode(', ', $newData['Genre']);
            foreach ($genres_imdb as $genre_imdb) {
                $genre = $entityManager->getRepository(Genre::class)->findOneBy(['name' => $genre_imdb]);
                if (!$genre) {
                    $genre = new Genre();
                    $genre->setName($genre_imdb);
                    $entityManager->persist($genre);
                }
                $series->addGenre($genre);
            }
        }

        if ($newData['Actors']) {
            $actorNames = explode(', ', $newData['Actors']);
            foreach ($actorNames as $actorName) {
                $actor = $entityManager->getRepository(Actor::class)->findOneBy(['name' => $actorName]);
                if (!$actor) {
                    $actor = new Actor();
                    $actor->setName($actorName);
                    $entityManager->persist($actor);
                }
                $series->addActor($actor);
            }
        }

        if ($newData['Country']) {
            $countryNames = explode(', ', $newData['Country']);
            foreach ($countryNames as $countryName) {
                $country = $entityManager->getRepository(Country::class)->findOneBy(['name' => $countryName]);
                if (!$country) {
                    $country = new Country();
                    $country->setName($countryName);
                    $entityManager->persist($country);
                }
                $series->addCountry($country);
            }
        }

        $seriesDetails = file_get_contents('http://www.omdbapi.com/?i=' . $imdb . '&apikey=' . $imdb_api_key);
        $seriesDetails = json_decode($seriesDetails, true);

        $totalSeasons = $seriesDetails['totalSeasons'];

        for ($seasonNumber = 1; $seasonNumber <= $totalSeasons; $seasonNumber++) {
            $seasonDetails = file_get_contents('http://www.omdbapi.com/?i=' . $imdb . '&season=' .
                $seasonNumber . '&apikey=' . $imdb_api_key);
            $seasonDetails = json_decode($seasonDetails, true);

            if (!is_array($seasonDetails)) {
                continue;
            }

            $season = $entityManager->getRepository(Season::class)
                ->findOneBy(['number' => $seasonNumber, 'series' => $series]);

            if (!$season) {
                $season = new Season();
                $season->setNumber($seasonNumber);
                $season->setSeries($series);
                $entityManager->persist($season);
            }

            if (array_key_exists('Episodes', $seasonDetails)) {
                foreach ($seasonDetails['Episodes'] as $episodeDetails) {
                    $episode = $entityManager->getRepository(Episode::class)
                        ->findOneBy(['number' => $episodeDetails['Episode'], 'season' => $season]);
                    if (!$episode) {
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
                        $season->setSeries($series);
                    }
                }
            }
            $series->addSeason($season);
            $entityManager->persist($season);
        }

        $entityManager->persist($series);
        $entityManager->flush();

        return true;
    }
}
