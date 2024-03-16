<?php

namespace App\Controller;

use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class CommentController extends AbstractController
{
    /**
     * @Route("/comments/{id}/delete", name="delete_comment")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function deleteComment(Request $request, $id, CommentRepository $commentRepository, EntityManagerInterface $entityManager): Response
    {
        $comment = $commentRepository->find($id);
        if (!$comment) {
            $this->addFlash('error', 'Comment not found.');
            return $this->redirect($request->headers->get('referer'));
        }

        // Check if the logged-in user is the author of the comment or has admin rights
        // Adapt this check to your application's security logic
        if ($comment->getAuthor()->getId() !== $this->getUser()->getId() && !$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('You do not have permission to delete this comment.');
        }

        // CSRF token validation for added security
        $submittedToken = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete'.$comment->getId(), $submittedToken)) {
            $entityManager->remove($comment);
            $entityManager->flush();

            $this->addFlash('success', 'Comment deleted successfully.');
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        // Redirect back to the page where the delete operation was initiated
        return $this->redirect($request->headers->get('referer'));
    }
}