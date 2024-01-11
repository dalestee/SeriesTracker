<?php

namespace App\Controller;

use App\Entity\Season;
use App\Form\SeasonType;
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
    #[Route('/', name: 'app_season_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $seasons = $entityManager
            ->getRepository(Season::class)
            ->findAll();

        return $this->render('season/index.html.twig', [
            'seasons' => $seasons,
        ]);
    }

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

    #[Route('/new', name: 'app_season_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $season = new Season();
        $form = $this->createForm(SeasonType::class, $season);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($season);
            $entityManager->flush();

            return $this->redirectToRoute('app_season_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('season/new.html.twig', [
            'season' => $season,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_season_show', methods: ['GET'])]
    public function show(Season $season): Response
    {
        return $this->render('season/show.html.twig', [
            'season' => $season,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_season_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Season $season, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SeasonType::class, $season);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_season_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('season/edit.html.twig', [
            'season' => $season,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_season_delete', methods: ['POST'])]
    public function delete(Request $request, Season $season, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $season->getId(), $request->request->get('_token'))) {
            $entityManager->remove($season);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_season_index', [], Response::HTTP_SEE_OTHER);
    }
}
