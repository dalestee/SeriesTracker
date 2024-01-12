<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserRoleFormType;
use App\Form\UserImpersonationFormType;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;

class AdminPanelController extends AbstractController
{
    #[Route('/admin/{page}', name: 'app_admin_panel', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager, PaginatorInterface $paginator, $page = 1): Response
    {
        if (isset($_GET['email'])) {
            $mail = $_GET['email'];
        
            $queryBuilder = $entityManager->getRepository(User::class)->createQueryBuilder('u');
            $queryBuilder->where('u.email LIKE :email')
                         ->setParameter('email', '%' . $mail . '%')
                         ->orderBy('u.admin', 'DESC');
        
            $query = $queryBuilder->getQuery();
        } else {
            $queryBuilder = $entityManager->getRepository(User::class)->createQueryBuilder('u');
            $queryBuilder->orderBy('u.admin', 'DESC');
            $query = $queryBuilder->getQuery();
        }
        
        $pagination = $paginator->paginate(
            $query, /* query NOT result */
            $page/*page number*/,
            10/*limit per page*/
        );
        
        $users = $pagination->getItems();

        $formImpersonation = $this->createForm(UserImpersonationFormType::class, null, [
                'action' => $this->generateUrl('app_admin_impersonate'),
            ]);

        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            $formRole = $this->createForm(UserRoleFormType::class, null, [
                'action' => $this->generateUrl('app_admin_change_role'),
            ])->createView();
        } else {
            $formRole = null;
        }
        
        return $this->render('admin_panel/index.html.twig', [
            'users' => $users,
            'form_impersonation' => $formImpersonation->createView(),
            'pagination' => $pagination,
            'form_role' => $formRole,
        ]);
    }

    #[Route('/admin/impersonate', name: 'app_admin_impersonate', methods: ['POST'])]
    public function impersonate(Request $request): Response
    {
        $form = $this->createForm(UserImpersonationFormType::class, null, [
            'action' => $this->generateUrl('app_admin_impersonate'),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
                $user = $form->getData()['user'];
            if ($user) {
                return $this->redirectToRoute('app_series_index', ['_switch_user' => $user->getUserIdentifier()]);
            }
        }
        return $this->redirectToRoute('app_admin_panel');
    }

    #[Route('/super_admin/role', name: 'app_admin_change_role', methods: ['POST'])]
    public function changeUserRole(EntityManagerInterface $entityManager, Request $request): Response
    {
        $form = $this->createForm(UserRoleFormType::class, null, [
            'action' => $this->generateUrl('app_admin_change_role'),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData()['user'];
            $user->setAdmin($form->getData()['role']);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_panel');
    }
}
