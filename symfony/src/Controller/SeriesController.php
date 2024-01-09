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

#[Route('/')]
class SeriesController extends AbstractController
{
    public function isUserLoggedIn(): bool
    {
        return $this->getUser() != null;
    }
    
    #[Route('/{page}', name: 'app_series_index', methods: ['GET'], requirements: ['page' => '\d+'])]
    public function index(EntityManagerInterface $entityManager, $page = 1): Response
    {
        $limit = 10;
        $offset = ($page - 1) * $limit;
        $seriesRepository = $entityManager->getRepository(Series::class);
        $totalSeries = $seriesRepository->count([]);
        $series = $seriesRepository->findBy([], null, $limit, $offset);
        $totalPages = ceil($totalSeries / $limit);
        return $this->render('series/index.html.twig', [
            'user' => $this->getUser(),
            'series' => $series,
            'totalPages' => $totalPages,
            'current_page' => $page,
        ]);
    }

    public function isfollow(User $user, Series $series): bool
    {
        if (!$this->isUserLoggedIn()) {
            return False;
        }
        else{
            return $user->isfollowingSeries($series);
        }
    }

    #[Route('/{page}/follow/{id}', name:'app_series_follow', methods: ['POST'], requirements: ['page' => '\d+'])]
    public function follow(EntityManagerInterface $entityManager,int $page,Series $series): Response
    {
        if (!$this->isUserLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }
        else{
            $user = $entityManager ->getRepository(User::class)->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
            $suivies = $this->isfollow($user, $series);
            if (!$suivies) {
                $user->addSeries($series);
                $entityManager ->flush();
            }
            return $this->redirectToRoute('app_series_index', ['page' => $page]);
        }
    }

    #[Route('/{page}/unfollow/{id}', name:'app_series_unfollow', methods: ['POST'], requirements: ['page' => '\d+'])]
    public function unfollow(EntityManagerInterface $entityManager,int $page, Series $series): Response
    {
        if (!$this->isUserLoggedIn()) {
            return $this->redirectToRoute('app_login');
        }
        else{
            $user = $entityManager ->getRepository(User::class)->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
            $suivies = $this->isfollow($user, $series);
            if ($suivies) {
                $user->removeSeries($series);
                $entityManager ->flush();
            }
            return $this->redirectToRoute('app_series_index', ['page' => $page]);
        }
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
