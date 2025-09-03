<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Wish;
use App\Entity\User;
use App\Services\Censurator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route]
class CommentController extends AbstractController
{
    #[Route('/wishes/{id}/comments', name: 'app_comment_add', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function add(Wish $wish = null, Request $request, EntityManagerInterface $em, Censurator $censurator): Response
    {
        if (!$wish) {
            throw $this->createNotFoundException('Idée introuvable');
        }
        $user = $this->getUser();
        if (!$user instanceof User) {
            $this->addFlash('error', 'Vous devez être connecté pour commenter.');
            return $this->redirectToRoute('app_login');
        }
        // interdit de commenter sa propre idée
        if ($wish->getAuthor() === $user->getPseudo()) {
            $this->addFlash('error', 'Vous ne pouvez pas commenter votre propre idée.');
            return $this->redirectToRoute('app_wish_detail', ['id' => $wish->getId()]);
        }

        if (!$this->isCsrfTokenValid('add_comment_' . $wish->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_wish_detail', ['id' => $wish->getId()]);
        }

        $content = trim((string) $request->request->get('content'));
        $rating = (int) $request->request->get('rating');
        $rating = max(1, min(5, $rating));
        if ($content === '') {
            $this->addFlash('error', 'Le commentaire ne peut pas être vide.');
            return $this->redirectToRoute('app_wish_detail', ['id' => $wish->getId()]);
        }

        // Application de la censure sur le contenu du commentaire
        $censoredContent = $censurator->purify($content);

        $comment = new Comment();
        $comment->setWish($wish);
        $comment->setAuthor($user);
        $comment->setContent($censoredContent);
        $comment->setRating($rating);

        $em->persist($comment);
        $em->flush();

        $this->addFlash('success', 'Commentaire ajouté.');
        return $this->redirectToRoute('app_wish_detail', ['id' => $wish->getId()]);
    }

    #[Route('/comments/{id}/edit', name: 'app_comment_edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function edit(Comment $comment = null, Request $request, EntityManagerInterface $em, Censurator $censurator): Response
    {
        if (!$comment) {
            throw $this->createNotFoundException('Commentaire introuvable');
        }
        $user = $this->getUser();
        if (!$user instanceof User || $comment->getAuthor()?->getId() !== $user->getId()) {
            $this->addFlash('error', "Vous n'êtes pas autorisé à modifier ce commentaire.");
            return $this->redirectToRoute('app_wish_detail', ['id' => $comment->getWish()->getId()]);
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('edit_comment_' . $comment->getId(), (string) $request->request->get('_token'))) {
                $this->addFlash('error', 'Token CSRF invalide.');
                return $this->redirectToRoute('app_comment_edit', ['id' => $comment->getId()]);
            }
            $content = trim((string) $request->request->get('content'));
            $rating = (int) $request->request->get('rating');
            $rating = max(1, min(5, $rating));
            if ($content === '') {
                $this->addFlash('error', 'Le commentaire ne peut pas être vide.');
                return $this->redirectToRoute('app_comment_edit', ['id' => $comment->getId()]);
            }

            // Application de la censure sur le contenu modifié du commentaire
            $censoredContent = $censurator->purify($content);

            $comment->setContent($censoredContent);
            $comment->setRating($rating);
            $em->flush();

            $this->addFlash('success', 'Commentaire modifié.');
            return $this->redirectToRoute('app_wish_detail', ['id' => $comment->getWish()->getId()]);
        }

        return $this->render('comment/edit.html.twig', [
            'comment' => $comment,
        ]);
    }

    #[Route('/comments/{id}/delete', name: 'app_comment_delete', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function delete(Comment $comment = null, Request $request, EntityManagerInterface $em): Response
    {
        if (!$comment) {
            throw $this->createNotFoundException('Commentaire introuvable');
        }
        $user = $this->getUser();
        $isAuthor = $user instanceof User && $comment->getAuthor()?->getId() === $user->getId();
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        if (!$isAuthor && !$isAdmin) {
            $this->addFlash('error', "Vous n'êtes pas autorisé à supprimer ce commentaire.");
            return $this->redirectToRoute('app_wish_detail', ['id' => $comment->getWish()->getId()]);
        }

        if (!$this->isCsrfTokenValid('delete_comment_' . $comment->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_wish_detail', ['id' => $comment->getWish()->getId()]);
        }

        $wishId = $comment->getWish()->getId();
        $em->remove($comment);
        $em->flush();
        $this->addFlash('success', 'Commentaire supprimé.');
        return $this->redirectToRoute('app_wish_detail', ['id' => $wishId]);
    }
}
