<?php

namespace App\Controller;

use App\Entity\Wish;
use App\Form\WishType;
use App\Repository\WishRepository;
use App\Repository\CommentRepository;
use App\Services\FileManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use App\Services\Censurator;

#[Route('/wishes', name: 'app_wish_')]
final class WishController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request, WishRepository $wishRepository): Response
    {
        $q = $request->query->get('q');
        $author = $request->query->get('author');
        $order = $request->query->get('order', 'DESC');
        $page = max(1, (int)$request->query->get('page', 1));
        $limit = 9;

        $result = $wishRepository->searchPublishedPaginated($q, $author, $order, $page, $limit);
        $authors = $wishRepository->getPublishedAuthors();

        return $this->render('wish/list.html.twig', [
            'wishes' => $result['wishes'],
            'q' => $q,
            'author' => $author,
            'order' => strtoupper($order) === 'ASC' ? 'ASC' : 'DESC',
            'authors' => $authors,
            'page' => $page,
            'totalPages' => $result['totalPages'],
            'totalItems' => $result['totalItems'],
        ]);
    }

    #[Route('/{id}', name: 'detail', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function detail(Wish $wish = null, CommentRepository $commentRepository): Response
    {
        if (!$wish) {
            throw $this->createNotFoundException('Idée introuvable');
        }

        $comments = $commentRepository->findByWishOrdered($wish);

        return $this->render('wish/detail.html.twig', [
            'wish' => $wish,
            'comments' => $comments,
        ]);
    }

    // Formulaire de création d'une idée
    #[Route('/formWish', name: 'formWish', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, FileManager $fileManager, Censurator $censurator): Response
    {
        $wish = new Wish();
        $user = $this->getUser();
        if (!$user || !method_exists($user, 'getPseudo')) {
            $this->addFlash('error', "Vous devez être connecté pour créer une idée.");
            return $this->redirectToRoute('app_wish_formWish');
        }
        $wish->setAuthor($user->getPseudo());
        $form = $this->createForm(WishType::class, $wish);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $originalDescription = $wish->getDescription();
                $censoredDescription = $censurator->purify($originalDescription);
                $originalTitle = $wish->getTitle();
                $censoredTitle = $censurator->purify($originalTitle);
                $wish->setTitle($censoredTitle);
                $wish->setDescription($censoredDescription);

                $file = $form->get('image')->getData();
                if ($file instanceof UploadedFile) {
                    try {
                        if ($name = $fileManager->upload($file, 'uploads', $form->get('image')->getName())) {
                            $wish->setImage($name);
                        }
                    } catch (\Exception $e) {
                        $this->addFlash('error', "Erreur lors de l'upload de l'image : " . $e->getMessage());
                        return $this->redirectToRoute('app_wish_formWish');
                    }
                }

                $em->persist($wish);
                $em->flush();
                $this->addFlash('success', 'Idée créée avec succès.');

                return $this->redirectToRoute('app_wish_detail', ['id' => $wish->getId()]);
            } else {
                $this->addFlash('error', 'Le formulaire contient des erreurs.');
                return $this->redirectToRoute('app_wish_formWish');
            }
        }

        return $this->render('wish/formWish.html.twig', [
            'form' => $form->createView(),
            'is_edit' => false,
        ]);
    }

    // Formulaire d'édition d'une idée
    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Wish $wish = null, EntityManagerInterface $em, FileManager $fileManager, Censurator $censurator): Response
    {
        if (!$wish) {
            throw $this->createNotFoundException('Idée introuvable');
        }

        // Autorisation: auteur OU admin
        $user = $this->getUser();
        if (!$user || !method_exists($user, 'getPseudo')) {
            $this->addFlash('error', "Vous devez être connecté pour modifier cette idée.");
            return $this->redirectToRoute('app_login');
        }
        $isAuthor = $wish->getAuthor() === $user->getPseudo();
        if (!$isAuthor) {
            $this->addFlash('error', "Vous n'êtes pas autorisé à modifier cette idée.");
            return $this->redirectToRoute('app_wish_detail', ['id' => $wish->getId()]);
        }

        $form = $this->createForm(WishType::class, $wish);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $originalDescription = $wish->getDescription();
            $censoredDescription = $censurator->purify($originalDescription);
            $originalTitle = $wish->getTitle();
            $censoredTitle = $censurator->purify($originalTitle);
            $wish->setTitle($censoredTitle);
            $wish->setDescription($censoredDescription);
            $file = $form->get('image')->getData();
            if ($file instanceof UploadedFile) {
                if ($name = $fileManager->upload($file, 'uploads', $form->get('image')->getName(), $wish->getImage() ?? '')) {
                    $wish->setImage($name);
                }
            }

            // Conserver l'auteur d'origine, ou le remettre à l'auteur actuel ? Ici on garde l'auteur initial
            $wish->setDateUpdated(new \DateTime());
            $em->flush();
            $this->addFlash('success', 'Idée mise à jour.');

            return $this->redirectToRoute('app_wish_detail', ['id' => $wish->getId()]);
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Le formulaire contient des erreurs.');
        }

        return $this->render('wish/formWish.html.twig', [
            'form' => $form->createView(),
            'is_edit' => true,
        ]);
    }

    // Suppression d'une idée (POST + CSRF)
    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function delete(Request $request, Wish $wish = null, EntityManagerInterface $em): Response
    {
        if (!$wish) {
            throw $this->createNotFoundException('Idée introuvable');
        }

        // Autorisation: auteur OU admin
        $user = $this->getUser();
        if (!$user || !method_exists($user, 'getPseudo')) {
            $this->addFlash('error', "Vous devez être connecté pour supprimer cette idée.");
            return $this->redirectToRoute('app_login');
        }
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $isAuthor = $wish->getAuthor() === $user->getPseudo();
        if (!$isAuthor && !$isAdmin) {
            $this->addFlash('error', "Vous n'êtes pas autorisé à supprimer cette idée.");
            return $this->redirectToRoute('app_wish_detail', ['id' => $wish->getId()]);
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_wish_' . $wish->getId(), $token)) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_wish_detail', ['id' => $wish->getId()]);
        }

        $em->remove($wish);
        $em->flush();
        $this->addFlash('success', 'Idée supprimée.');

        return $this->redirectToRoute('app_wish_list');
    }
}
