<?php

namespace App\Controller;

use App\Entity\Season;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Episode;
use App\Entity\User;

#[Route('/season')]
class SeasonController extends AbstractController
{
    public function isEpisodeViewed(Episode $episode): bool
    {
        $user = $this->getUser();
        return $user->isEpisodeViewed($episode);
    }

    public function allPrecendentViewed(EntityManagerInterface $entityManager, Request $request, Episode $episode): void
    {
        $season = $episode->getSeason();
        $episodes = $season->getEpisodes();
        $episodeNumber = $episode->getNumber();
        $seriesSeason = $season->getSeries()->getSeasons();

        foreach ($seriesSeason as $serieSeason) {
            if ($serieSeason->getNumber() < $season->getNumber()) {
                foreach ($serieSeason->getEpisodes() as $episode) {
                    if (!$this->isEpisodeViewed($episode)) {
                        $this->viewEpisode($entityManager, $episode);
                    }
                }
            }
        }

        foreach ($episodes as $episode) {
            if ($episode->getNumber() < $episodeNumber) {
                if (!$this->isEpisodeViewed($episode)) {
                    $this->viewEpisode($entityManager, $episode);
                }
            }
        }
    }

    #[Route('/viewAllPrevious/{id}', name: 'episode_view_precedent', methods: ['POST'])]
    public function episodeViewPrevious(
        EntityManagerInterface $entityManager,
        Request $request,
        Episode $episode
    ): Response {
        $this->allPrecendentViewed($entityManager, $request, $episode);
        $user = $entityManager->getRepository(User::class)
                ->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        $user->addEpisode($episode);
        $entityManager->flush();

        return $this->redirectToRoute(
            'app_series_show',
            ['id' => $episode->getSeason()->getSeries()->getId()],
            Response::HTTP_SEE_OTHER
        );
    }

    #[Route('/view/{id}', name: 'episode_view', methods: ['POST'])]
    public function viewEpisode(
        EntityManagerInterface $entityManager,
        Episode $episode
    ): Response {
        $user = $user = $entityManager->getRepository(User::class)
                    ->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        $user->addEpisode($episode);
        if (!$user->isfollowingSeries($episode->getSeason()->getSeries())) {
            $user->addSeries($episode->getSeason()->getSeries());
        }
        $entityManager->flush();

        return $this->redirectToRoute(
            'app_series_show',
            ['id' => $episode->getSeason()->getSeries()->getId()],
            Response::HTTP_SEE_OTHER
        );
    }

    #[Route('/unview/{id}', name: 'episode_unview', methods: ['POST'])]
    public function unviewEpisode(
        EntityManagerInterface $entityManager,
        Episode $episode
    ): Response {
        $user = $user = $entityManager->getRepository(User::class)
                    ->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);

        $user->removeEpisode($episode);
        $entityManager->flush();

        return $this->redirectToRoute(
            'app_series_show',
            ['id' => $episode->getSeason()->getSeries()->getId()],
            Response::HTTP_SEE_OTHER
        );
    }

    #[Route('/viewAllSeasonEpisodes/{id}', name: 'season_view', methods: ['POST'])]
    public function viewAllSeasonEpisodes(
        EntityManagerInterface $entityManager,
        Season $season
    ): Response {
        $user = $user = $entityManager->getRepository(User::class)
                    ->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);

        foreach ($season->getEpisodes() as $episode) {
            if (!$user->isEpisodeViewed($episode)) {
                $user->addEpisode($episode);
            }
        }
        if (!$user->isfollowingSeries($season->getSeries())) {
            $user->addSeries($season->getSeries());
        }
        $entityManager->flush();

        return $this->redirectToRoute(
            'app_series_show',
            ['id' => $season->getSeries()->getId()],
            Response::HTTP_SEE_OTHER
        );
    }

    #[Route('/unviewAllSeasonEpisodes/{id}', name: 'season_unview', methods: ['POST'])]
    public function unviewAllSeasonEpisodes(
        EntityManagerInterface $entityManager,
        Season $season
    ): Response {
        $user = $user = $entityManager->getRepository(User::class)
                    ->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);

        foreach ($season->getEpisodes() as $episode) {
            if ($user->isEpisodeViewed($episode)) {
                $user->removeEpisode($episode);
            }
        }
        $entityManager->flush();

        return $this->redirectToRoute(
            'app_series_show',
            ['id' => $season->getSeries()->getId()],
            Response::HTTP_SEE_OTHER
        );
    }

    #[Route('/view/{id}', name: 'season_view_season', methods: ['POST'])]
    public function viewSeason(
        EntityManagerInterface $entityManager,
        Season $season
    ): Response {
        $user = $user = $entityManager->getRepository(User::class)
                    ->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);

        foreach ($season->getEpisodes() as $episode) {
            $user->addEpisode($episode);
        }
        $entityManager->flush();

        return $this->redirectToRoute(
            'app_series_show',
            ['id' => $season->getSeries()->getId()],
            Response::HTTP_SEE_OTHER
        );
    }
}
