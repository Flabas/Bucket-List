<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:create-test-user', description: 'Crée un utilisateur de test pour tester la réinitialisation de mot de passe')]
class CreateTestUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Vérifier si l'utilisateur existe déjà
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'test@example.com']);

        if ($existingUser) {
            $io->success('L\'utilisateur test@example.com existe déjà !');
            $io->text('Vous pouvez utiliser cet email pour tester la réinitialisation de mot de passe.');
            return Command::SUCCESS;
        }

        // Créer l'utilisateur de test
        $user = new User();
        $user->setPseudo('testuser');
        $user->setEmail('test@example.com');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success('Utilisateur de test créé avec succès !');
        $io->text([
            'Email: test@example.com',
            'Mot de passe: password123',
            '',
            'Vous pouvez maintenant tester la réinitialisation de mot de passe avec cet email.'
        ]);

        return Command::SUCCESS;
    }
}
