<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/admin')]
class AdminPanelController extends AbstractController
{
    #[Route('/{page}', name: 'app_admin_panel', methods: ['GET'])]
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
}
