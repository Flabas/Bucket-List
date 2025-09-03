<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

#[AsCommand(name: 'app:mail:test', description: 'Envoie un email de test via le Mailer pour vérifier la configuration SMTP')]
class TestMailCommand extends Command
{
    public function __construct(private readonly MailerInterface $mailer)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('to', InputArgument::REQUIRED, 'Adresse destinataire (ex: test@example.com)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $to = (string) $input->getArgument('to');

        $io->section('Envoi du mail de test');
        $io->text('Destinataire: ' . $to);

        try {
            $email = (new Email())
                ->from(new Address('no-reply@local.test', 'Bucket-List Test'))
                ->to($to)
                ->subject('Test mail — Papercut SMTP')
                ->html('<p>Ceci est un email de test envoyé par Symfony Mailer.</p><p>Si vous voyez ce message dans Papercut, la configuration fonctionne.</p>');

            $this->mailer->send($email);
            $io->success('Email envoyé. Vérifiez Papercut SMTP.');
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error('Échec de l\'envoi: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

