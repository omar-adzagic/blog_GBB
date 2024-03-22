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
use App\Service\TranslationService;
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
    private $translationService;
    private $contentTranslationService;
    public function __construct(TranslationService $translationService, ContentTranslationService $contentTranslationService)
    {
        $this->translationService = $translationService;
        $this->contentTranslationService = $contentTranslationService;
    }

    /**
     * @Route("/tags", name="admin_tag_index")
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
     */
    public function index(
        TagRepository $tagRepository,
        PaginationService $paginationService
    ): Response
    {
        $queryBuilder = $tagRepository->findAllLatestQB();
        $pagination = $paginationService->paginate($queryBuilder);
        $tagDTOs = TagDTO::createFromTags($pagination->getItems(), $this->contentTranslationService);
        $pagination->setItems($tagDTOs);

        return $this->render('admin/tags/index.html.twig', ['pagination' => $pagination]);
    }

    /**
     * @Route("/tags/create", name="admin_tag_create")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function create(Request $request, TagManager $tagManager)
    {
        $newTag = new Tag();
        $form = $this->createForm(TagType::class, $newTag);
        $this->contentTranslationService->setLocaleCreateFormFields($form, ['name']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $tag = $form->getData();
                $tagManager->saveTagCreate($tag);

                $this->contentTranslationService->setCreateTranslatableFormFields(
                    $form, $tag, ['name'], TagTranslation::class
                );

                $this->addFlash(
                    'success',
                    $this->translationService->messageTranslate(
                        'flash_messages.operation_success',
                        ['{{entity}}' => 'the_tag', '{{action}}' => 'actions.created']
                    )
                );
                return $this->redirectToRoute('admin_tag_index');
            } catch (\Exception $e) {
                $this->addFlash(
                    'error',
                    $this->translationService->messageTranslate(
                        'flash_messages.operation_failed',
                        ['{{entity}}' => 'the_tag', '{{action}}' => 'actions.created']
                    )
                );
            }
        }

        $responseData = [
            'tag' => $newTag,
            'form' => $form,
            'locales' => $this->contentTranslationService->getSupportedLocales()
        ];
        return $this->renderForm('admin/tags/create.html.twig', $responseData);
    }

    /**
     * @Route("/tags/{tag}", name="admin_tag_show")
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
     */
    public function show(Tag $tag): Response
    {
        $tagDTO = TagDTO::createFromTag($tag, $this->contentTranslationService);
        return $this->render('admin/tags/show.html.twig', [
            'tag' => $tagDTO
        ]);
    }

    /**
     * @Route("/tags/{tag}/edit", name="admin_tag_edit")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function edit(Tag $tag, Request $request)
    {
        $form = $this->createForm(TagType::class, $tag);
        $this->contentTranslationService->setLocaleEditFormFields($form, $tag);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->contentTranslationService->setEditTranslatableFields($form, $tag);

                $this->addFlash(
                    'success',
                    $this->translationService->messageTranslate(
                        'flash_messages.operation_success',
                        ['{{entity}}' => 'the_tag', '{{action}}' => 'actions.updated']
                    )
                );
                return $this->redirectToRoute('admin_tag_index');
            } catch (\Exception $e) {
                $this->addFlash(
                    'error',
                    $this->translationService->messageTranslate(
                        'flash_messages.operation_failed',
                        ['{{entity}}' => 'the_tag', '{{action}}' => 'actions.updated']
                    )
                );
            }
        }

        $responseData = [
            'form' => $form,
            'tag' => $tag,
            'locales' => $this->contentTranslationService->getSupportedLocales(),
        ];
        return $this->renderForm('admin/tags/edit.html.twig', $responseData);
    }

    /**
     * @Route("/tags/{tag}/delete", name="admin_tag_delete")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function delete(Tag $tag, EntityManagerInterface $entityManager): RedirectResponse
    {
        try {
            $entityManager->remove($tag);
            $entityManager->flush();

            $this->addFlash(
                'success',
                $this->translationService->messageTranslate(
                    'flash_messages.operation_success',
                    ['{{entity}}' => 'the_tag', '{{action}}' => 'actions.deleted']
                )
            );
        } catch (\Exception $e) {
            $this->addFlash(
                'error',
                $this->translationService->messageTranslate(
                    'flash_messages.operation_failed',
                    ['entity' => 'the_tag', 'action' => 'actions.deleted']
                )
            );
        }

        return $this->redirectToRoute('admin_tag_index');
    }
}
