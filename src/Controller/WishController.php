<?php

namespace App\Controller;

use App\Entity\Wish;
use App\Form\WishType;
use App\Repository\WishRepository;
use App\Services\FileManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/wishes', name: 'app_wish_')]
final class WishController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request, WishRepository $wishRepository): Response
    {
        $q = $request->query->get('q');
        $author = $request->query->get('author');
        $order = $request->query->get('order', 'DESC');

        $wishes = $wishRepository->searchPublished($q, $author, $order);
        $authors = $wishRepository->getPublishedAuthors();

        return $this->render('wish/list.html.twig', [
            'wishes' => $wishes,
            'q' => $q,
            'author' => $author,
            'order' => strtoupper($order) === 'ASC' ? 'ASC' : 'DESC',
            'authors' => $authors,
        ]);
    }

    #[Route('/{id}', name: 'detail', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function detail(Wish $wish = null): Response
    {
        if (!$wish) {
            throw $this->createNotFoundException('Idée introuvable');
        }

        return $this->render('wish/detail.html.twig', [
            'wish' => $wish,
        ]);
    }

    // Formulaire de création d'une idée
    #[Route('/formWish', name: 'formWish', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, FileManager $fileManager): Response
    {
        $wish = new Wish();
        $form = $this->createForm(WishType::class, $wish);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('image')->getData();
            if ($file instanceof UploadedFile) {

                if ($name = $fileManager->upload($file, 'uploads', $form->get('image')->getName()))
                {
                    $wish->setImage($name);
                }
            }


            $em->persist($wish);
            $em->flush();
            $this->addFlash('success', 'Idée créée avec succès.');

            return $this->redirectToRoute('app_wish_detail', ['id' => $wish->getId()]);
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Le formulaire contient des erreurs.');
        }

        return $this->render('wish/formWish.html.twig', [
            'form' => $form->createView(),
            'is_edit' => false,
        ]);
    }

    // Formulaire d'édition d'une idée
    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Wish $wish = null, EntityManagerInterface $em, FileManager $fileManager): Response
    {
        if (!$wish) {
            throw $this->createNotFoundException('Idée introuvable');
        }

        $form = $this->createForm(WishType::class, $wish);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('image')->getData();
            if ($file instanceof UploadedFile) {
                if ($name = $fileManager->upload($file, 'uploads', $form->get('image')->getName(), $wish->getImage())) {
                    $wish->setImage($name);
                }
            }

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
