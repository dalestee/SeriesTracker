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

    #[Route('{id}', name: 'episode_view', methods: ['POST'])]
    public function episode_view(EntityManagerInterface $entityManager, Request $request): Response
    {
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        $episode = $entityManager->getRepository(Episode::class)->findOneBy(['id' => $request->request->get('episode_id')]);
        
        $user->addEpisode($episode);
        $entityManager->flush();

        return $this->redirectToRoute('app_season_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('{id}', name: 'episode_unview', methods: ['POST'])]
    public function episode_unview(EntityManagerInterface $entityManager, Request $request): Response
    {
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        $episode = $entityManager->getRepository(Episode::class)->findOneBy(['id' => $request->request->get('episode_id')]);
        
        $user->removeEpisode($episode);
        $entityManager->flush();

        return $this->redirectToRoute('app_season_index', [], Response::HTTP_SEE_OTHER);
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
        if ($this->isCsrfTokenValid('delete'.$season->getId(), $request->request->get('_token'))) {
            $entityManager->remove($season);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_season_index', [], Response::HTTP_SEE_OTHER);
    }
}
