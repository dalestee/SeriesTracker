<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    #[Route('/user/{id_user}/', name: 'app_user')]
    public function index(
        PaginatorInterface $paginator,
        UserRepository $userRepository,
        Request $request,
        int $id_user
    ): Response {
        if (!$this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('app_login');
        }

        $user = $userRepository->find($id_user);

        $page_series = $request->query->getInt('page_series', 1);
        $page_ratings = $request->query->getInt('page_ratings', 1);

        if ($user) {
            $series_suivies = $paginator->paginate(
                $user->getSeries(),
                $page_series,
                10
            );

            $ratings_user = $paginator->paginate(
                $user->getRatings(),
                $page_ratings,
                10
            );
        } else {
            $series_suivies = null;
            $ratings_user = null;
        }

        return $this->render('user/index.html.twig', [
            'user' => $user,
            'series_suivies' => $series_suivies,
            'ratings_user' => $ratings_user,
            'app_action' => 'app_user',
            'param_action' => ['page_series' => $page_series, 'page_ratings' => $page_ratings]
        ]);
    }
}
