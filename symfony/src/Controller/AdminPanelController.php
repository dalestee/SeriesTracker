<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Rating;
use App\Repository\RatingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Knp\Component\Pager\PaginatorInterface;
use App\Repository\UserRepository;

#[Route('/admin')]
class AdminPanelController extends AbstractController
{
    #[Route('/dashboard/{page}', name: 'app_admin_panel', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager, PaginatorInterface $paginator, $page = 1): Response
    {
        if (isset($_GET['email'])) {
            $mail = $_GET['email'];

            $queryBuilder = $entityManager->getRepository(User::class)->createQueryBuilder('u');
            $queryBuilder->where('u.email LIKE :email')
                ->setParameter('email', '%' . $mail . '%')

                ->orderBy('u.registerDate', 'DESC')
                ->orderBy('u.admin', 'DESC')
            ;

            $query = $queryBuilder->getQuery();
        } else {
            $queryBuilder = $entityManager->getRepository(User::class)->createQueryBuilder('u');
            $queryBuilder->orderBy('u.registerDate', 'DESC')
                         ->orderBy('u.admin', 'DESC')
                         ;
            $query = $queryBuilder->getQuery();
        }

        $pagination = $paginator->paginate(
            $query, /* query NOT result */
            $page/*page number*/,
            10/*limit per page*/
        );

        $users = $pagination->getItems();

        return $this->render('admin_panel/index.html.twig', [
            'users' => $users,
            'pagination' => $pagination,
        ]);
    }

    #[Route('/personify/{id}', name: 'app_admin_personify', methods: ['GET'])]
    public function personify(User $user): Response
    {
        if ($user) {
            if ($user->isSuperAdmin()) {
                // No one can personify a super admin
                $this->addFlash('danger', 'You cannot personify a super admin.');
                return $this->redirectToRoute('app_admin_panel');
            }
            // Admins can personify users and super admins can personify admins and users
            return $this->redirectToRoute('app_series_index', ['_switch_user' => $user->getUserIdentifier()]);
        }

        return $this->redirectToRoute('app_admin_panel');
    }

    #[Route('/super_admin/role/{id}', name: 'app_admin_change_role', methods: ['POST'])]
    public function changeUserRole(EntityManagerInterface $entityManager, User $user): Response
    {
        if ($user) {
            if ($user->isSuperAdmin()) {
                $this->addFlash('danger', 'You cannot change the role of a super admin.');
                return $this->redirectToRoute('app_admin_panel');
            }

            if ($user->isAdmin()) {
                $user->setAdmin(0);
            } else {
                $user->setAdmin(1);
            }

            $entityManager->persist($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_panel');
    }

    #[Route('/changePassword', name: 'app_admin_change_password', methods: ['GET'])]
    public function changePassword(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        if (!$this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('app_login');
        }

        $userId = $request->query->get('userId');
        $password = $request->query->get('password');

        $user = $entityManager->getRepository(User::class)->find($userId);
        $hashedPassword = $passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
        $entityManager->flush();
        return $this->redirectToRoute('app_admin_panel');
    }

    #[Route('/moderate/{page_ratings}', name: 'app_admin_panel_moderate')]
    public function moderate(
        RatingRepository $ratingRepository,
        Request $request,
        EntityManagerInterface $entityManager,
        PaginatorInterface $paginator,
        int $page_ratings = 1
    ): Response {
        $id = $request->get('id');
        $moderate = $request->get('moderate');
        if ($id != null) {
            if ($moderate) {
                $rating = $ratingRepository->find($id);
                $rating->setModerate(true);
                $entityManager->persist($rating);
                $entityManager->flush();
            } else {
                $rating = $ratingRepository->find($id);
                $entityManager->remove($rating);
                $entityManager->flush();
            }
        }
        $ratingquery = $ratingRepository->queryRatingNoModerate();
        $ratings = $paginator->paginate(
            $ratingquery,
            $page_ratings,
            10
        );

        return $this->render('admin_panel/moderate.html.twig', [
            'ratings' => $ratings,
            'app_action' => 'app_admin_panel_moderate',
            'page_ratings' => $page_ratings
        ]);
    }
}
