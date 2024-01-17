<?php

namespace App\Controller;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/omdb')]
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
            'apikey' => '5140c72f',  // Assurez-vous de remplacer 'votre_api_key' par votre clé réelle
            'type' => 'series'
        ];

        if ($imdb) {
            $query['i'] = $imdb;
        } else {
            $query['s'] = $name;
        }

        $response = $client->request('GET', 'http://www.omdbapi.com/', ['query' => $query]);

        

        $series = json_decode($response->getContent(), true);

        return $this->render('omdb/index.html.twig', [
            'series' => $series,
        ]);
    }
}
