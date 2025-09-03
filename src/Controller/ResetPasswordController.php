<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordFormType;
use App\Form\ResetPasswordRequestFormType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

#[Route('/reset-password')]
class ResetPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;

    public function __construct(
        private ResetPasswordHelperInterface $resetPasswordHelper,
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Display & process form to request a password reset.
     */
    #[Route('', name: 'app_forgot_password_request')]
    public function request(Request $request, MailerInterface $mailer, TranslatorInterface $translator, LoggerInterface $logger = null): Response
    {
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $email */
            $email = $form->get('email')->getData();

            return $this->processSendingPasswordResetEmail($email, $mailer, $translator, $logger);
        }

        return $this->render('reset_password/request.html.twig', [
            'requestForm' => $form,
        ]);
    }

    /**
     * Confirmation page after a user has requested a password reset.
     */
    #[Route('/check-email', name: 'app_check_email')]
    public function checkEmail(): Response
    {
        // Generate a fake token if the user does not exist or someone hit this page directly.
        // This prevents exposing whether or not a user was found with the given email address or not
        if (null === ($resetToken = $this->getTokenObjectFromSession())) {
            $resetToken = $this->resetPasswordHelper->generateFakeResetToken();
        }

        return $this->render('reset_password/check_email.html.twig', [
            'resetToken' => $resetToken,
        ]);
    }

    /**
     * Validates and process the reset URL that the user clicked in their email.
     */
    #[Route('/reset/{token}', name: 'app_reset_password')]
    public function reset(Request $request, UserPasswordHasherInterface $passwordHasher, TranslatorInterface $translator, ?string $token = null): Response
    {
        if ($token) {
            // We store the token in session and remove it from the URL, to avoid the URL being
            // loaded in a browser and potentially leaking the token to 3rd party JavaScript.
            $this->storeTokenInSession($token);

            return $this->redirectToRoute('app_reset_password');
        }

        $token = $this->getTokenFromSession();

        if (null === $token) {
            throw $this->createNotFoundException('No reset password token found in the URL or in the session.');
        }

        try {
            /** @var User $user */
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            $this->addFlash('reset_password_error', sprintf(
                '%s - %s',
                $translator->trans(ResetPasswordExceptionInterface::MESSAGE_PROBLEM_VALIDATE, [], 'ResetPasswordBundle'),
                $translator->trans($e->getReason(), [], 'ResetPasswordBundle')
            ));

            return $this->redirectToRoute('app_forgot_password_request');
        }

        // The token is valid; allow the user to change their password.
        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // A password reset token should be used only once, remove it.
            $this->resetPasswordHelper->removeResetRequest($token);

            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // Encode(hash) the plain password, and set it.
            $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            $this->entityManager->flush();

            // The session is cleaned up after the password has been changed.
            $this->cleanSessionAfterReset();

            return $this->redirectToRoute('app_home');
        }

        return $this->render('reset_password/reset.html.twig', [
            'resetForm' => $form,
        ]);
    }

    private function processSendingPasswordResetEmail(string $emailFormData, MailerInterface $mailer, TranslatorInterface $translator, LoggerInterface $logger = null): RedirectResponse
    {
        // Debug : afficher l'email recherché
        $this->addFlash('info', 'Recherche d\'un utilisateur avec l\'email: ' . $emailFormData);

        $user = $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => $emailFormData,
        ]);

        // Do not reveal whether a user account was found or not.
        if (!$user) {
            if ($logger) {
                $logger->info('Reset password request for non-existent email', ['email' => $emailFormData]);
            }
            // Debug temporaire : indiquer que l'utilisateur n'a pas été trouvé
            $this->addFlash('warning', 'AUCUN utilisateur trouvé avec cet email dans la base de données !');
            return $this->redirectToRoute('app_check_email');
        }

        // Debug : confirmer que l'utilisateur a été trouvé
        $this->addFlash('info', 'Utilisateur trouvé: ' . $user->getEmail() . ' (' . $user->getPseudo() . ')');

        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
            $this->addFlash('info', 'Token de réinitialisation généré avec succès');
        } catch (ResetPasswordExceptionInterface $e) {
            if ($logger) {
                $logger->error('Failed to generate reset token', ['email' => $emailFormData, 'error' => $e->getMessage()]);
            }
            $this->addFlash('error', 'Erreur lors de la génération du token: ' . $e->getMessage() . ' (Raison: ' . $e->getReason() . ')');
            return $this->redirectToRoute('app_check_email');
        } catch (\Exception $e) {
            if ($logger) {
                $logger->error('Failed to generate reset token - General exception', ['email' => $emailFormData, 'error' => $e->getMessage()]);
            }
            $this->addFlash('error', 'Erreur générale lors de la génération du token: ' . $e->getMessage());
            return $this->redirectToRoute('app_check_email');
        }

        $email = (new TemplatedEmail())
            ->from(new Address('no-reply@bucket-list.fr', 'BucketList Admin'))
            ->to((string) $user->getEmail())
            ->subject('Your password reset request')
            ->htmlTemplate('reset_password/email.html.twig')
            ->context([
                'resetToken' => $resetToken,
            ])
        ;

        try {
            $mailer->send($email);
            if ($logger) {
                $logger->info('Password reset email sent successfully', [
                    'email' => $user->getEmail(),
                    'token_id' => $resetToken->getToken()
                ]);
            }

            // Message de debug temporaire pour vérifier que l'envoi a lieu
            $this->addFlash('success', 'Email de réinitialisation envoyé avec succès ! Vérifiez Papercut sur le port 1025.');

        } catch (\Exception $e) {
            if ($logger) {
                $logger->error('Failed to send password reset email', [
                    'email' => $user->getEmail(),
                    'error' => $e->getMessage()
                ]);
            }

            // Message de debug pour les erreurs
            $this->addFlash('error', 'Erreur lors de l\'envoi de l\'email: ' . $e->getMessage());

            // On continue même si l'email échoue pour ne pas révéler l'existence de l'utilisateur
        }

        // Store the token object in session for retrieval in check-email route.
        $this->setTokenObjectInSession($resetToken);

        return $this->redirectToRoute('app_check_email');
    }
}
