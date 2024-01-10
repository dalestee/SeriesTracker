<?php

namespace App\Controller;

use App\Entity\Series;
use App\Entity\User;
use App\Form\SeriesType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/')]
class SeriesController extends AbstractController
{
    public function isUserLoggedIn(): bool
    {
        return $this->getUser() != null;
    }
    
    #[Route('/{page}', name: 'app_series_index', methods: ['GET'], requirements: ['page' => '\d+'])]
    public function index(EntityManagerInterface $entityManager, PaginatorInterface $paginator, $page = 1): Response
    {
        $seriesRepository = $entityManager->getRepository(Series::class);
        $query = $seriesRepository->createQueryBuilder('p')->getQuery();

        $pagination = $paginator->paginate(
            $query, /* query NOT result */
            $page/*page number*/,
            10/*limit per page*/
        );

        return $this->render('series/index.html.twig', [
            'user' => $this->getUser(),
            'app_action' => 'app_series_index',
            'pagination' => $pagination
        ]);
    }

    public function isfollow(User $user, Series $series): bool
    {
        if (!$this->isUserLoggedIn()) {
            return false;
        } else {
            return $user->isfollowingSeries($series);
        }
    }

    #[Route('/{page}/follow/{id}', name:'app_series_follow', methods: ['POST'], requirements: ['page' => '\d+'])]
    public function follow(EntityManagerInterface $entityManager, Request $request, int $page, Series $series): Response
    {
        if (!$this->isUserLoggedIn()) {
            return $this->redirectToRoute('app_login');
        } else {
            $user = $entityManager->getRepository(User::class)
                    ->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
            $suivies = $this->isfollow($user, $series);
            if (!$suivies) {
                $user->addSeries($series);
                $entityManager ->flush();
            }

            $route = $request->headers->get('referer');
            return $this->redirect($route);
        }
    }

    #[Route('/{page}/unfollow/{id}', name:'app_series_unfollow', methods: ['POST'], requirements: ['page' => '\d+'])]
    public function unfollow(
        EntityManagerInterface $entityManager,
        Request $request,
        int $page,
        Series $series
    ): Response {
        if (!$this->isUserLoggedIn()) {
            return $this->redirectToRoute('app_login');
        } else {
            $user = $user = $entityManager->getRepository(User::class)
                    ->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
            $suivies = $this->isfollow($user, $series);
            if ($suivies) {
                $user->removeSeries($series);
                $entityManager ->flush();
            }

            $route = $request->headers->get('referer');
            return $this->redirect($route);
        }
    }

    #[Route('/listSeriesFollow/{page}', name: 'app_series_list_follow', methods: ['GET'])]
    public function listFollow(EntityManagerInterface $entityManager, int $page = 1): Response
    {
        if (!$this->isUserLoggedIn()) {
            return $this->redirectToRoute('app_login');
        } else {
            $user = $user = $entityManager->getRepository(User::class)
                    ->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
            $series = $user->getSeries();

            $limit = 10;
            $offset = ($page - 1) * $limit;
            $totalSeries = $series->count();
            $series = $series->slice($offset, $limit);
            $totalPages = ceil($totalSeries / $limit);

            return $this->render('series/index.html.twig', [
                'user' => $this->getUser(),
                'app_action' => 'app_series_list_follow',
                'series' => $series,
                'totalPages' => $totalPages,
                'current_page' => $page,
            ]);
        }
    }

    #[Route('/viewAllSeries/{id}', name: 'app_series_view_all', methods: ['GET'])]
    public function viewAllSeries(EntityManagerInterface $entityManager, Request $request, Series $series): Response
    {
        $user = $user = $entityManager->getRepository(User::class)
                    ->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        $seriesSeasons = $series->getSeasons();
        foreach ($seriesSeasons as $season) {
            $seasonEpisodes = $season->getEpisodes();
            foreach ($seasonEpisodes as $episode) {
                if (!$user->isEpisodeViewed($episode)) {
                    $user->addEpisode($episode);
                }
            }
        }
        $entityManager->flush();
        return $this->redirectToRoute('app_series_show', ['id' => $series->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/unviewAllSeries/{id}', name: 'app_series_unview_all', methods: ['GET'])]
    public function unviewAllSeries(EntityManagerInterface $entityManager, Request $request, Series $series): Response
    {
        $user = $user = $entityManager->getRepository(User::class)
                    ->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        $seriesSeasons = $series->getSeasons();
        foreach ($seriesSeasons as $season) {
            $seasonEpisodes = $season->getEpisodes();
            foreach ($seasonEpisodes as $episode) {
                if ($user->isEpisodeViewed($episode)) {
                    $user->removeEpisode($episode);
                }
            }
        }
        $entityManager->flush();
        return $this->redirectToRoute('app_series_show', ['id' => $series->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/poster/{id}', name: 'app_series_poster', methods: ['GET'])]
    public function poster(Series $series): Response
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'image/jpeg');
        $response->setContent(stream_get_contents($series->getPoster()));

        return $response;
    }

    #[Route('/new', name: 'app_series_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $series = new Series();
        $form = $this->createForm(SeriesType::class, $series);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($series);
            $entityManager->flush();

            return $this->redirectToRoute('app_series_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('series/new.html.twig', [
            'series' => $series,
            'form' => $form,
        ]);
    }

    #[Route('/showSerie/{id}', name: 'app_series_show', methods: ['GET'])]
    public function show(Series $series): Response
    {
        return $this->render('series/show.html.twig', [
            'series' => $series,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_series_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Series $series, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SeriesType::class, $series);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_series_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('series/edit.html.twig', [
            'series' => $series,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_series_delete', methods: ['POST'])]
    public function delete(Request $request, Series $series, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$series->getId(), $request->request->get('_token'))) {
            $entityManager->remove($series);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_series_index', [], Response::HTTP_SEE_OTHER);
    }
}
