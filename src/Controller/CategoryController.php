<?php
namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/categories', name: 'app_category_')]
class CategoryController extends AbstractController
{
    #[Route('/create', name: 'create', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('create_category', (string) $request->request->get('_token'))) {
                $this->addFlash('error', 'Token CSRF invalide.');
                return $this->redirectToRoute('app_category_create');
            }
            $name = trim((string) $request->request->get('name'));
            if ($name !== '') {
                $category = new Category();
                $category->setName($name);
                $em->persist($category);
                $em->flush();
                $this->addFlash('success', 'Catégorie créée !');
                return $this->redirectToRoute('app_category_list');
            }
            $this->addFlash('error', 'Le nom est obligatoire.');
        }
        return $this->render('category/create.html.twig');
    }

    #[Route('/delete/{id}', name: 'delete', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function delete(Category $category, EntityManagerInterface $em, Request $request): Response
    {

        if (!$this->isCsrfTokenValid('delete_category_' . $category->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_category_list');
        }

        $em->remove($category);
        $em->flush();
        $this->addFlash('success', 'Catégorie supprimée !');
        return $this->redirectToRoute('app_category_list');
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(CategoryRepository $repo): Response
    {
        $categories = $repo->findAll();
        return $this->render('category/list.html.twig', [
            'categories' => $categories
        ]);
    }

    #[Route('/edit/{id}', name: 'edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function edit(Category $category, Request $request, EntityManagerInterface $em): Response
    {

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('edit_category_' . $category->getId(), (string) $request->request->get('_token'))) {
                $this->addFlash('error', 'Token CSRF invalide.');
                return $this->redirectToRoute('app_category_edit', ['id' => $category->getId()]);
            }
            $name = trim((string) $request->request->get('name'));
            if ($name !== '') {
                $category->setName($name);
                $em->flush();
                $this->addFlash('success', 'Catégorie modifiée !');
                return $this->redirectToRoute('app_category_list');
            }
            $this->addFlash('error', 'Le nom est obligatoire.');
        }
        return $this->render('category/edit.html.twig', [
            'category' => $category
        ]);
    }
}
