<?php

namespace App\Command;

use App\Entity\ResetPasswordRequest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:clean-reset-requests', description: 'Nettoie les demandes de réinitialisation de mot de passe expirées ou existantes')]
class CleanResetRequestsCommand extends Command
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Supprimer toutes les demandes de réinitialisation existantes
        $repository = $this->entityManager->getRepository(ResetPasswordRequest::class);
        $requests = $repository->findAll();

        $count = count($requests);

        foreach ($requests as $request) {
            $this->entityManager->remove($request);
        }

        $this->entityManager->flush();

        $io->success("$count demande(s) de réinitialisation supprimée(s).");
        $io->text('Vous pouvez maintenant tester à nouveau la réinitialisation de mot de passe.');

        return Command::SUCCESS;
    }
}
