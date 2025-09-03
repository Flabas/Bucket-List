<?php

namespace App\Controller;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

class MailTestController extends AbstractController
{
    #[Route('/test-mail', name: 'app_test_mail')]
    public function testMail(MailerInterface $mailer): Response
    {
        $results = [];

        // Test 1: Email simple
        try {
            $email = (new Email())
                ->from(new Address('no-reply@bucket-list.fr', 'BucketList Test'))
                ->to('test@example.com')
                ->subject('Test Email Simple')
                ->text('Ceci est un test d\'email simple.')
                ->html('<p>Ceci est un test d\'<strong>email simple</strong>.</p>');

            $mailer->send($email);
            $results['simple'] = 'SUCCESS: Email simple envoyé';
        } catch (\Exception $e) {
            $results['simple'] = 'ERROR: ' . $e->getMessage();
        }

        // Test 2: Email template (comme reset password)
        try {
            $email = (new TemplatedEmail())
                ->from(new Address('no-reply@bucket-list.fr', 'BucketList Test'))
                ->to('test@example.com')
                ->subject('Test Email Template')
                ->htmlTemplate('reset_password/email.html.twig')
                ->context([
                    'resetToken' => (object)['token' => 'test-token-123'],
                ]);

            $mailer->send($email);
            $results['template'] = 'SUCCESS: Email template envoyé';
        } catch (\Exception $e) {
            $results['template'] = 'ERROR: ' . $e->getMessage();
        }

        // Test 3: Vérification de la configuration
        $mailerDsn = $_ENV['MAILER_DSN'] ?? 'NON DEFINI';
        $results['config'] = 'MAILER_DSN: ' . $mailerDsn;

        return $this->render('mail_test_results.html.twig', [
            'results' => $results
        ]);
    }
}
