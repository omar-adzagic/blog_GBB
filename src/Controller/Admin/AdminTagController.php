<?php

// src/Controller/Admin/AdminPostController.php
namespace App\Controller\Admin;

use App\Entity\Tag;
use App\Form\TagType;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/admin")
 */
class AdminTagController extends AbstractController
{
    /**
     * @Route("/tags", name="admin_tag_index")
     */
    public function index(TagRepository $tagRepository)
    {
        $tags = $tagRepository->findAll();
        // Fetch tags from the database
        // Render the template with tags data
        return $this->render('admin/tags/index.html.twig', [
            'tags' => $tags
        ]);
    }

    /**
     * @Route("/tags/{tag}", name="admin_tag_show")
     */
    public function show(
        Tag $tag,
        TagRepository $tagRepository
    )
    {
        return $this->render('admin/tags/show.html.twig', [
            'tag' => $tag
        ]);
    }

    /**
     * @Route("/tags/{tag}/edit", name="admin_tag_edit")
     */
    public function edit(Tag $tag, Request $request, TagRepository $tagRepository)
    {
        $form = $this->createForm(TagType::class, $tag);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $tagRepository->add($form->getData(), true);
            $this->addFlash('success', 'Tag has been updated.');
            return $this->redirectToRoute('admin_tag_index');
        }

        return $this->renderForm(
            'admin/tags/edit.html.twig',
            ['form' => $form, 'tag' => $tag]
        );
    }

    /**
     * @Route("/tags/{tag}/delete", name="admin_tag_delete")
     */
    public function delete(Tag $tag, EntityManagerInterface $entityManager)
    {
        $entityManager->remove($tag);
        $entityManager->flush();

        $this->addFlash('success', 'Tag has been deleted.');

        return $this->redirectToRoute('admin_tag_index');
    }
}