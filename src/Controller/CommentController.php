<?php

namespace App\Controller;

use App\Repository\CommentRepository;
use App\Service\TranslationService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class CommentController extends AbstractController
{
    private $translationService;
    public function __construct(TranslationService $translationService)
    {
        $this->translationService = $translationService;
    }

    /**
     * @Route("/comments/{id}/delete", name="delete_comment")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     * @IsGranted("ROLE_ADMIN")
     */
    public function deleteComment(
        int $id,
        Request $request,
        CommentRepository $commentRepository,
        EntityManagerInterface $entityManager
    ): Response
    {
        $comment = $commentRepository->find($id);
        if (!$comment) {
            $this->addFlash(
                'error',
                $this->translationService->messageTranslate(
                    'flash_messages.entity_not_found',
                    ['{{entity}}' => $this->translationService->messageTranslate('the_comment')]
                )
            );
            return $this->redirect($request->headers->get('referer'));
        }

        // CSRF token validation for added security
        $submittedToken = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete' . $comment->getId(), $submittedToken)) {
            $entityManager->remove($comment);
            $entityManager->flush();

            $this->addFlash(
                'success',
                $this->translationService->messageTranslate(
                    'flash_messages.operation_success',
                    ['{{entity}}' => 'the_comment', '{{action}}' => 'actions.deleted']
                )
            );
        } else {
            $this->addFlash(
                'error',
                $this->translationService->messageTranslate(
                    'flash_messages.invalid_CSRF_token'
                )
            );
        }

        // Redirect back to the page where the delete operation was initiated
        return $this->redirect($request->headers->get('referer'));
    }
}