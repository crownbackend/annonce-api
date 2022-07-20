<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    private UserRepository $userRepository;
    private JWTTokenManagerInterface $JWTTokenManager;
    private UserPasswordHasherInterface $passwordHasher;
    private EntityManagerInterface $entityManager;

    public function __construct(UserRepository $userRepository, UserPasswordHasherInterface $passwordHasher,
                                JWTTokenManagerInterface $JWTTokenManager, EntityManagerInterface $entityManager)
    {
        $this->userRepository = $userRepository;
        $this->JWTTokenManager = $JWTTokenManager;
        $this->passwordHasher = $passwordHasher;
        $this->entityManager = $entityManager;
    }

    #[Route(path: '/api/login', name: 'app_login_api', methods: ['POST'])]
    public function loginApi(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->userRepository->findOneBy(['email' => $data['username']]);
        if($user == null) {
            return $this->json(["notAccount" => 0], 200);
        }
        if($user->isVerified() == 0) {
            return  $this->json(['enable' => 0], 200);
        }
        if($user && $this->passwordHasher->isPasswordValid($user, $data['password'])) {
            $user->setLastLogin(new \DateTime());
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            $response = [
                'token' => [
                    'user' => $user,
                    'token' => $this->JWTTokenManager->create($user)
                ]
            ];
            return $this->json($response, 201);
        } else {
            return $this->json('error login', 400);
        }
    }
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
