<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\ResetPasswordRequest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/debug')]
#[IsGranted('ROLE_ADMIN')]
class DebugController extends AbstractController
{
    #[Route('/users', name: 'app_debug_list_users')]
    public function listUsers(EntityManagerInterface $entityManager): Response
    {
        $users = $entityManager->getRepository(User::class)->findAll();
        $resetRequests = $entityManager->getRepository(ResetPasswordRequest::class)->findAll();

        // Statistiques
        $stats = [
            'total_users' => count($users),
            'admin_users' => count(array_filter($users, fn($user) => in_array('ROLE_ADMIN', $user->getRoles()))),
            'pending_reset_requests' => count($resetRequests),
        ];

        return $this->render('debug/users.html.twig', [
            'users' => $users,
            'resetRequests' => $resetRequests,
            'stats' => $stats,
        ]);
    }

    #[Route('/create-user', name: 'app_debug_create_user')]
    public function createUser(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        // Vérifier si l'utilisateur existe déjà
        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => 'test@example.com']);

        if ($existingUser) {
            $this->addFlash('warning', 'L\'utilisateur test@example.com existe déjà !');
            return $this->redirectToRoute('app_debug_list_users');
        }

        // Créer l'utilisateur de test
        $user = new User();
        $user->setPseudo('testuser');
        $user->setEmail('test@example.com');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($passwordHasher->hashPassword($user, 'password123'));

        $entityManager->persist($user);
        $entityManager->flush();

        $this->addFlash('success', 'Utilisateur de test créé avec succès ! Email: test@example.com, Mot de passe: password123');
        return $this->redirectToRoute('app_debug_list_users');
    }

    #[Route('/clean-reset-requests', name: 'app_debug_clean_reset_requests')]
    public function cleanResetRequests(EntityManagerInterface $entityManager): Response
    {
        $repository = $entityManager->getRepository(ResetPasswordRequest::class);
        $requests = $repository->findAll();

        $count = count($requests);

        foreach ($requests as $request) {
            $entityManager->remove($request);
        }

        $entityManager->flush();

        $this->addFlash('success', "$count demande(s) de réinitialisation supprimée(s).");
        return $this->redirectToRoute('app_debug_list_users');
    }

    #[Route('/create-admin', name: 'app_debug_create_admin')]
    public function createAdmin(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        // Vérifier si l'admin existe déjà
        $existingAdmin = $entityManager->getRepository(User::class)->findOneBy(['email' => 'admin@example.com']);

        if ($existingAdmin) {
            $this->addFlash('warning', 'L\'administrateur admin@example.com existe déjà !');
            return $this->redirectToRoute('app_debug_list_users');
        }

        // Créer l'utilisateur admin
        $admin = new User();
        $admin->setPseudo('admin');
        $admin->setEmail('admin@example.com');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($passwordHasher->hashPassword($admin, 'admin123'));

        $entityManager->persist($admin);
        $entityManager->flush();

        $this->addFlash('success', 'Administrateur créé avec succès ! Email: admin@example.com, Mot de passe: admin123');
        return $this->redirectToRoute('app_debug_list_users');
    }

    #[Route('/delete-test-users', name: 'app_debug_delete_test_users')]
    public function deleteTestUsers(EntityManagerInterface $entityManager): Response
    {
        $testEmails = ['test@example.com', 'admin@example.com'];
        $deletedCount = 0;

        foreach ($testEmails as $email) {
            $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($user && $user->getId() !== $this->getUser()->getId()) { // Ne pas supprimer l'utilisateur connecté
                $entityManager->remove($user);
                $deletedCount++;
            }
        }

        $entityManager->flush();

        $this->addFlash('success', "$deletedCount utilisateur(s) de test supprimé(s).");
        return $this->redirectToRoute('app_debug_list_users');
    }
}
