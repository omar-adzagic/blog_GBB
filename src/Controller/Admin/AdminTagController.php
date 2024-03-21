<?php

namespace App\Controller\Admin;

use App\DTO\TagDTO;
use App\Entity\Tag;
use App\Entity\TagTranslation;
use App\Form\TagType;
use App\Repository\TagRepository;
use App\Service\ContentTranslationService;
use App\Service\PaginationService;
use App\Service\TagManager;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    public function index(
        TagRepository $tagRepository,
        PaginationService $paginationService,
        ContentTranslationService $contentTranslationService
    ): Response
    {
        $queryBuilder = $tagRepository->findAllLatestQB();
        $pagination = $paginationService->paginate($queryBuilder);
        $tagDTOs = TagDTO::createFromTags($pagination->getItems(), $contentTranslationService);
        $pagination->setItems($tagDTOs);

        return $this->render('admin/tags/index.html.twig', ['pagination' => $pagination]);
    }

    /**
     * @Route("/tags/create", name="admin_tag_create")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function create(
        Request $request,
        TagManager $tagManager,
        ContentTranslationService $contentTranslationService
    )
    {
        $newTag = new Tag();
        $form = $this->createForm(TagType::class, $newTag);
        $contentTranslationService->setLocaleCreateFormFields($form, ['name']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $tag = $form->getData();
            $tagManager->saveTagCreate($tag);

            $contentTranslationService->setCreateTranslatableFormFields(
                $form, $tag, ['name'], TagTranslation::class
            );

            $this->addFlash('success', 'Tag has been created.');
            return $this->redirectToRoute('admin_tag_index');
        }

        $responseData = [
            'tag' => $newTag,
            'form' => $form,
            'locales' => $contentTranslationService->getSupportedLocales()
        ];
        return $this->renderForm('admin/tags/create.html.twig', $responseData);
    }

    /**
     * @Route("/tags/{tag}", name="admin_tag_show")
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
     */
    public function show(Tag $tag, ContentTranslationService $contentTranslationService): Response
    {
        $tagDTO = TagDTO::createFromTag($tag, $contentTranslationService);
        return $this->render('admin/tags/show.html.twig', [
            'tag' => $tagDTO
        ]);
    }

    /**
     * @Route("/tags/{tag}/edit", name="admin_tag_edit")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function edit(
        Tag $tag,
        Request $request,
        ContentTranslationService $contentTranslationService
    )
    {
        $form = $this->createForm(TagType::class, $tag);
        $contentTranslationService->setLocaleEditFormFields($form, $tag);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $contentTranslationService->setEditTranslatableFields($form, $tag);

            $this->addFlash('success', 'Tag has been updated.');
            return $this->redirectToRoute('admin_tag_index');
        }

        $responseData = [
            'form' => $form,
            'tag' => $tag,
            'locales' => $contentTranslationService->getSupportedLocales()
        ];
        return $this->renderForm('admin/tags/edit.html.twig', $responseData);
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
