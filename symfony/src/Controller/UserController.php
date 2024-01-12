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
    #[Route('/user/{id_user}/{page_serie}', name: 'app_user', defaults: ['page_serie' => 1])]
    public function index(
        PaginatorInterface $paginator,
        UserRepository $userRepository,
        int $id_user,
        int $page_serie = 1
    ): Response {
        if (!$this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('app_login');
        }

        $user = $userRepository->find($id_user);

        if ($user) {
            $series_suivies = $paginator->paginate(
                $user->getSeries(),
                $page_serie,
                10
            );
        } else {
            $series_suivies = null;
        }
        
        return $this->render('user/index.html.twig', [
            'user' => $user,
            'series_suivies' => $series_suivies,
            'app_action' => 'app_user'
        ]);
    }
}
