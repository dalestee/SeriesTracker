<?php

namespace App\Controller;

use App\Entity\Rating;

use App\Repository\UserRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Series;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Form\ProfileFormType;

#[Route('/user')]
class UserController extends AbstractController
{
    #[Route('/show/{id_user}/', name: 'app_user_show')]
    public function index(
        EntityManagerInterface $entityManager,
        PaginatorInterface $paginator,
        UserRepository $userRepository,
        Request $request,
        int $id_user
    ): Response {

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

            $users_following = $entityManager
                ->getRepository(User::class)
                ->queryFindUsersFollowing($user->getId());

            dump($users_following->getResult());
        } else {
            $series_suivies = null;
            $ratings_user = null;
        }

        return $this->render('user/index.html.twig', [
            'user' => $user,
            'series_suivies' => $series_suivies,
            'series_view' => $series_view,
            'ratings_user' => $ratings_user,
            'app_action' => 'app_user_show',
            'param_action' => ['page_series' => $page_series, 'page_ratings' => $page_ratings]
        ]);
    }
    
    #[Route('/followed', name: 'app_user_followed_users')]
    public function followedUsers(
        EntityManagerInterface $entityManager,
        PaginatorInterface $paginator,
        Request $request,
    ): Response {
        $user = $entityManager->getRepository(User::class)
                ->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
    
        $page = $request->query->getInt('page', 1);
    
        if ($user) {
            $pagination = $paginator->paginate(
                $entityManager
                    ->getRepository(User::class)
                    ->queryFindUsersFollowing($user->getId())->getResult(),
                $page,
                10
            );
        } else {
            $pagination = null;
        }
    
        return $this->render('user/followed_users.html.twig', [
            'user' => $user,
            'pagination' => $pagination,
            'app_action' => 'app_user_followed_users',
            'param_action' => [],
            'page' => $page
        ]);
    }

    #[Route('/profile', name: 'profile', methods: ['GET', 'POST'])]
    public function profile(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository
    ): Response {

        $user = $userRepository
            ->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);

        $form2 = $this->createForm(ProfileFormType::class, $user);
        $form2->handleRequest($request);

        if ($form2->isSubmitted() && $form2->isValid()) {
            $oldpassword = $form2->get('oldPassword')->getData();
            if ($oldpassword != null) {
                $firstPassword = $form2->get('newPassword')->get('first')->getData();
                $secondPassword = $form2->get('newPassword')->get('second')->getData();
                if ($firstPassword === $secondPassword && $userPasswordHasher->isPasswordValid($user, $oldpassword)) {
                    // encode the plain password
                    $user->setPassword(
                        $userPasswordHasher->hashPassword(
                            $user,
                            $firstPassword
                        )
                    );
                }
            }

            $entityManager->persist($user);
            $entityManager->flush();
        }
        $entityManager->refresh($user);

        return $this->render('user/profile.html.twig', [
            'form2' => $form2->createView(),
            'user' => $user,
            'show_search_form' => false
        ]);
    }
    #[Route('/follow/{id_user}', name: 'app_user_follow', methods: ['GET'])]
    public function followUser(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        int $id_user
    ): Response {

        $user = $userRepository->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        $user_followed = $userRepository->find($id_user);

        if ($user->getId() != $id_user && !$user->isFollowing($user_followed)) {
            $user->addFollowing($user_followed);
            $entityManager->persist($user);
            $entityManager->persist($user_followed);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_user_show', ['id_user' => $id_user]);
    }

    #[Route('/unfollow/{id_user}', name: 'app_user_unfollow', methods: ['GET'])]
    public function unfollowUser(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        int $id_user
    ): Response {
        $user = $userRepository->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        $user_followed = $userRepository->find($id_user);

        if ($user->getId() != $id_user && $user->isFollowing($user_followed)) {
            $user->removeFollowing($user_followed);
            $entityManager->persist($user);
            $entityManager->persist($user_followed);
            $entityManager->flush();
        }
        
        return $this->redirectToRoute('app_user_show', ['id_user' => $id_user]);
    }

    #[Route('/newsFeed', name: 'app_user_news_feed', methods: ['GET'])]
    public function newsFeed(
        EntityManagerInterface $entityManager,
        PaginatorInterface $paginator,
        UserRepository $userRepository,
        Request $request
    ): Response {

        $user = $userRepository->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        $allRatings = [];

        if ($user) {
            $friends = $user->getFollowing();
            foreach ($friends as $friend) {
                $friendRatings = $entityManager->getRepository(Rating::class)->findBy(['user' => $friend]);
                if ($friend->getId() != $user->getId()) {
                    $allRatings = array_merge($allRatings, $friendRatings);
                };
            }
            usort($allRatings, function ($a, $b) {
                return $a->getDate() < $b->getDate();
            });
        }

        $limit = 10;

        $page = $request->query->getInt('page', 1);

        $pagination = $paginator->paginate(
            $allRatings,
            $request->query->getInt('page', 1),
            $limit
        );

        return $this->render('user/news_feed.html.twig', [
            'user' => $user,
            'allRatings' => $allRatings,
            'pagination' => $pagination,
            'app_action' => 'app_user_news_feed',
            'param_action' => [],
            'page' => $page
        ]);
    }
}
