<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use LogicException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AuthController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('auth/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/register', name: 'app_register_form', methods: ['GET'])]
    public function registerPage(): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('auth/register.html.twig');
    }

    #[Route('/register', name: 'app_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): Response {
        $isJson = str_contains((string) $request->headers->get('content-type'), 'json');
        $payload = $isJson ? json_decode($request->getContent(), true) : $request->request->all();

        if (!is_array($payload)) {
            return $this->handleRegisterError('Invalid payload', $isJson, null);
        }

        $email = isset($payload['email']) ? strtolower(trim((string) $payload['email'])) : null;
        $password = isset($payload['password']) ? (string) $payload['password'] : null;

        if (!$email || !$password) {
            return $this->handleRegisterError('Email and password are required', $isJson, $email);
        }

        if (\strlen($password) < 8) {
            return $this->handleRegisterError('Password must have at least 8 characters', $isJson, $email);
        }

        if ($userRepository->findOneBy(['email' => $email])) {
            return $this->handleRegisterError('User with this email already exists', $isJson, $email, Response::HTTP_CONFLICT);
        }

        $user = new User();
        $user->setEmail($email);

        $violations = $validator->validate($user);
        if (\count($violations) > 0) {
            $messages = [];
            foreach ($violations as $violation) {
                $messages[] = $violation->getPropertyPath() . ': ' . $violation->getMessage();
            }

            return $this->handleRegisterError($messages, $isJson, $email);
        }

        $hashedPassword = $passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $entityManager->persist($user);
        $entityManager->flush();

        if ($isJson) {
            return new JsonResponse(
                ['id' => $user->getId(), 'email' => $user->getEmail()],
                Response::HTTP_CREATED
            );
        }

        $this->addFlash('success', 'Account created successfully. You can now log in.');
        return $this->redirectToRoute('app_login');
    }

    #[Route('/account', name: 'app_account', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function account(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('account/account.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/account/password', name: 'app_account_password', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function updatePassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $newPassword = (string) $request->request->get('password');

        if (\strlen($newPassword) < 8) {
            $this->addFlash('error', 'Password must have at least 8 characters.');
            return $this->redirectToRoute('app_account');
        }

        $hashed = $passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashed);
        $entityManager->flush();

        $this->addFlash('success', 'Password updated successfully.');
        return $this->redirectToRoute('app_account');
    }

    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): void
    {
        throw new LogicException('Logout is handled by Symfony security.');
    }

    private function handleRegisterError(string|array $message, bool $isJson, ?string $email, int $status = Response::HTTP_BAD_REQUEST): Response
    {
        $text = is_array($message) ? implode(', ', $message) : $message;

        if ($isJson) {
            return new JsonResponse(['error' => $message], $status);
        }

        return $this->render('auth/register.html.twig', [
            'last_email' => $email,
            'form_error' => $text,
        ]);
    }
}

