<?php

namespace App\Controller\Admin;

use App\Entity\Tag;
use App\Form\TagType;
use App\Repository\TagRepository;
use App\Service\PaginationService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin")
 * @IsGranted("ROLE_ADMIN")
 */
class AdminTagController extends AbstractController
{
    /**
     * @Route("/tags", name="admin_tag_index")
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
     */
    public function index(TagRepository $tagRepository, PaginationService $paginationService): Response
    {
        $queryBuilder = $tagRepository->findAllLatestQB();
        $pagination = $paginationService->paginate($queryBuilder);

        return $this->render('admin/tags/index.html.twig', [
            'pagination' => $pagination
        ]);
    }

    /**
     * @Route("/tags/search", name="admin_tag_search", methods={"GET"})
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
     */
    public function search(Request $request, TagRepository $tagRepository): JsonResponse
    {
        $searchTerm = $request->query->get('q', '');

        $tags = $tagRepository->findByNameLike($searchTerm);

        $tagsArray = array_map(function($tag) {
            return [
                'id' => $tag->getId(),
                'name' => $tag->getName(),
            ];
        }, $tags);

        return $this->json(['tags' => $tags], Response::HTTP_OK, [], [
            'groups' => ['tag_search']
        ]);
    }

    /**
     * @Route("/tags/create", name="admin_tag_create")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function create(Request $request, TagRepository $tagRepository)
    {
        $form = $this->createForm(TagType::class, new Tag());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $tagRepository->add($form->getData(), true);
            $this->addFlash('success', 'Tag has been created.');
            return $this->redirectToRoute('admin_tag_index');
        }

        return $this->renderForm(
            'admin/tags/create.html.twig',
            ['form' => $form]
        );
    }

    /**
     * @Route("/tags/{tag}", name="admin_tag_show")
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
     */
    public function show(Tag $tag): Response
    {
        return $this->render('admin/tags/show.html.twig', [
            'tag' => $tag
        ]);
    }

    /**
     * @Route("/tags/{tag}/edit", name="admin_tag_edit")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
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
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function delete(Tag $tag, EntityManagerInterface $entityManager): RedirectResponse
    {
        $entityManager->remove($tag);
        $entityManager->flush();

        $this->addFlash('success', 'Tag has been deleted.');

        return $this->redirectToRoute('admin_tag_index');
    }
}
