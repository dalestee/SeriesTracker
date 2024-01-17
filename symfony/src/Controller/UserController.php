<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Series;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Form\ProfileFormType;

class UserController extends AbstractController
{
    #[Route('/user/{id_user}/', name: 'app_user')]
    public function index(
        EntityManagerInterface $entityManager,
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
                $entityManager
                ->getRepository(Series::class)
                ->querySeriesSuiviesTrieParVisionnage($user->getId()),
                $page_series,
                10
            );
            $rating = $userRepository->queryFindRatingFromUser($user);

            $series_id = [];
            foreach ($series_suivies->getItems() as $serie) {
                $series_id[] = $serie[0]->getId();
            }


            $series_view =  $entityManager
                ->getRepository(Series::class)
                ->querySeriesSuiviesTrieParVisionnage($user->getId(), $series_id);

            $ratings_user = $paginator->paginate(
                $rating->getResult(),
                $page_ratings,
                12
            );
        } else {
            $series_suivies = null;
            $ratings_user = null;
        }

        return $this->render('user/index.html.twig', [
            'user' => $user,
            'series_suivies' => $series_suivies,
            'series_view' => $series_view,
            'ratings_user' => $ratings_user,
            'app_action' => 'app_user',
            'param_action' => ['page_series' => $page_series, 'page_ratings' => $page_ratings]
        ]);
    }

    #[Route('/admin_panel/changePassword', name: 'app_admin_change_password', methods: ['GET'])]
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
        dump($password);

        $user = $entityManager->getRepository(User::class)->find($userId);
        $hashedPassword = $passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
        $entityManager->flush();
        return $this->redirectToRoute('app_user', ['id_user' => $userId]);
    }


    #[Route('/profile', name: 'profile')]
    public function profile(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        if ($this->getUser() == null) {
            $user = null;
        } else {
            $user = $entityManager->getRepository(User::class)
                ->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        }
        $form2 = $this->createForm(ProfileFormType::class, $user);
        $form2->handleRequest($request);
        $test = $form2->isSubmitted();
        dump(date('H:i:s'));
        dump($test);
        dump($form2);
        if ($form2->isSubmitted() && $form2->isValid()) {
            dump($user);
            $firstPassword = $form2->get('newPassword')->get('first')->getData();
            $secondPassword = $form2->get('newPassword')->get('second')->getData();
            $oldpassword = $form2->get('oldPassword')->getData();
            dump($oldpassword);
            if ($firstPassword === $secondPassword && $userPasswordHasher->isPasswordValid($user, $oldpassword)) {
                // encode the plain password
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $firstPassword
                    )
                );
                $user->setName($form2->get('name')->getData());
                $user->setCountry($form2->get('country')->getData());

                $entityManager->persist($user);
                $entityManager->flush();
            }
        }

        return $this->render('user/profile.html.twig',[
            'form2' => $form2->createView(),
            'user' => $user,
            'show_search_form' => false
        ]);
    }
}
