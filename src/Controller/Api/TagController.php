<?php

namespace App\Controller\Api;

use App\DTO\TagDTO;
use App\Repository\TagRepository;
use App\Repository\TagTranslationRepository;
use App\Service\ContentTranslationService;
use App\Service\TranslationService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api")
 * @IsGranted("ROLE_ADMIN")
 */
class TagController extends AbstractController
{
    /**
     * @Route("/tags/search", name="api_tag_search", methods={"GET"})
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
     */
    public function search(
        Request $request,
        TagRepository $tagRepository,
        TagTranslationRepository $tagTranslationRepository,
        ContentTranslationService $contentTranslationService
    ): JsonResponse
    {
        $searchTerm = $request->query->get('q', '');
        $tagTranslations = $tagTranslationRepository->findByNameLike($searchTerm);
        $tagIds = array_map(function ($item) { return $item->getObject()->getId(); }, $tagTranslations);
        $tags = $tagRepository->findByIds($tagIds);
        $tagsDTO = TagDTO::createFromTags($tags, $contentTranslationService);

        return $this->json(['tags' => $tagsDTO], Response::HTTP_OK);
    }
}
