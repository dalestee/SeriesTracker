<?php

namespace App\Controller;

use App\Entity\Series;
use App\Entity\User;
use App\Entity\Rating;
use App\Form\SeriesType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
use App\Repository\SeriesRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Entity;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/')]
class SeriesController extends AbstractController
{
    public function isUserLoggedIn(): bool
    {
        return $this->getUser() != null;
    }

    #[Route('/{page_serie}', name: 'app_series_index', methods: ['GET'], requirements: ['page_serie' => '\d+'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        PaginatorInterface $paginator,
        $page_serie = 1
    ): Response {

        $session = $request->getSession();

        // Check if the session already has a 'seed' value
        if (!$session->has('seed')) {
            // If not, set a new 'seed' value
            $session->set('seed', rand());
        }

        $seed = $session->get('seed');

        $query = $entityManager->getRepository(Series::class)->queryRandom($seed);

        $search = $request->query->get('search');
        if (!empty($search)) {
            $query = $entityManager->getRepository(Series::class)->findByKeyWordInAll($search);
        }

        $pagination = $paginator->paginate(
            $query, /* query NOT result */
            $page_serie/*page number*/,
            10/*limit per page*/
        );

        return $this->render('series/index.html.twig', [
            'user' => $this->getUser(),
            'app_action' => 'app_series_index',
            'pagination' => $pagination,
            'param_action' => ['search' => $search]
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

    #[Route('/series/follow/{id}', name: 'app_series_follow', methods: ['POST'])]
    public function follow(EntityManagerInterface $entityManager, Request $request, Series $series): Response
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



    #[Route('/series/unfollow/{id}', name: 'app_series_unfollow', methods: ['POST'])]
    public function unfollow(
        EntityManagerInterface $entityManager,
        Request $request,
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

    #[Route('/listSeriesFollow/{page_serie}', name: 'app_series_list_follow', methods: ['GET'])]
    public function listFollow(
        Request $request,
        SeriesRepository $seriesRepository,
        EntityManagerInterface $entityManager,
        PaginatorInterface $paginator,
        int $page_serie = 1
    ): Response {
        if (!$this->isUserLoggedIn()) {
            return $this->redirectToRoute('app_login');
        } else {
            $user = $entityManager->getRepository(User::class)
                ->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
            $seriesQuery = $user->getSeries();

            $search = $request->query->get('search');
            if (empty($search) == false) {
                $seriesQuery = $seriesRepository->findByKeyWordInSeriesFollowing($user, $search);
            } else {
                $seriesQuery = $entityManager->getRepository(Series::class)
                        ->querySeriesSuiviesTrieParVisionnage($user->getId());
            }
                    
            $pagination = $paginator->paginate(
                $seriesQuery, /* query NOT result */
                $page_serie/*page number*/,
                10/*limit per page*/
            );

            return $this->render('series/series_follow.html.twig', [
                'user' => $this->getUser(),
                'app_action' => 'app_series_list_follow',
                'pagination' => $pagination,
                'param_action' => ['search' => $search],
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
        if (!$user->isfollowingSeries($series)) {
            $user->addSeries($series);
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

    #[Route('{id}/rate/{note}', name: 'app_series_rate', methods: ['POST'])]
    public function rate(EntityManagerInterface $entityManager, Request $request, Series $series, int $note): Response
    {
        if (!$this->isUserLoggedIn()) {
            return $this->redirectToRoute('app_login');
        } else {
            $user = $entityManager->getRepository(User::class)
                ->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);

            $rating = new Rating();
            $rating->setUser($user);
            $rating->setSeries($series);
            $rating->setValue($note);
            $rating->setDate(new \DateTime());
            $entityManager->persist($rating);

            $entityManager->flush();

            return $this->redirectToRoute('app_series_show', ['id' => $series->getId()], Response::HTTP_SEE_OTHER);
        }
    }

    #[Route('{id}/unrate', name: 'app_series_unrate', methods: ['POST'])]
    public function unrate(EntityManagerInterface $entityManager, Request $request, Series $series): Response
    {
        if (!$this->isUserLoggedIn()) {
            return $this->redirectToRoute('app_login');
        } else {
            $user = $entityManager->getRepository(User::class)
                ->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
            $rating = $entityManager->getRepository(Rating::class)
                ->findOneBy(['user' => $user, 'series' => $series]);
            $entityManager->remove($rating);
            $entityManager->flush();

            return $this->redirectToRoute('app_series_show', ['id' => $series->getId()], Response::HTTP_SEE_OTHER);
        }
    }

    #[Route('{id}/rateComment', name: 'app_series_rate_comment', methods: ['POST'])]
    public function rateComment(EntityManagerInterface $entityManager, Request $request, Series $series): Response
    {
        if (!$this->isUserLoggedIn()) {
            return $this->redirectToRoute('app_login');
        } else {
            $user = $entityManager->getRepository(User::class)
                ->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
            $rating = $entityManager->getRepository(Rating::class)
                ->findOneBy(['user' => $user, 'series' => $series]);
            $rating->setComment($request->request->get('comment'));
            $rating->setDate(new \DateTime());
            $entityManager->flush();

            return $this->redirectToRoute('app_series_show', ['id' => $series->getId()], Response::HTTP_SEE_OTHER);
        }
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

    #[Route('/showSerie/{id}/{{page_ratings}}', name: 'app_series_show', methods: ['GET'])]
    public function show(
        EntityManagerInterface $entityManager,
        PaginatorInterface $paginator,
        Series $series,
        int $page_ratings = 1
    ): Response {

        $ratingQuery = $entityManager->getRepository(Rating::class)
            ->queryRatingsBySeries($series->getId());

        $pagination = $paginator->paginate(
            $ratingQuery, /* query NOT result */
            $page_ratings/*page number*/,
            3/*limit per page*/
        );

        return $this->render('series/show.html.twig', [
            'series' => $series,
            'app_action' => 'app_series_show',
            'param_action' => ['id' => $series->getId()],
            'pagination' => $pagination,
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
        if ($this->isCsrfTokenValid('delete' . $series->getId(), $request->request->get('_token'))) {
            $entityManager->remove($series);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_series_index', [], Response::HTTP_SEE_OTHER);
    }

    // #[Route('/search', name: 'app_series_search', methods: ['GET'])]
    // public function search(Request $request, EntityManagerInterface $entityManager): Response
    // {
    //     $search = $request->query->get('search');
    //     $seriesRepository = $entityManager->getRepository(Series::class);
    //     var_dump($search);
    //     $series = $seriesRepository->createQueryBuilder('s')
    //         ->where('s.title LIKE :search')
    //         ->setParameter('search', '%' . $search . '%')
    //         ->getQuery()
    //         ->getResult();

    //     return $this->render('series/search.html.twig', [
    //         'series' => $series,
    //         'search' => $search,
    //     ]);
    // }
}
