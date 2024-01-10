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

class AdminPanelController extends AbstractController
{
    #[Route('/admin', name: 'app_admin_panel', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $users = $entityManager
            ->getRepository(User::class)
            ->findby([], ['admin' => 'DESC']);

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
