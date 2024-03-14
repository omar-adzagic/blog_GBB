<?php

namespace App\Controller;

use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CommentController extends AbstractController
{
    /**
     * @Route("/comments/{id}", name="delete_comment", methods={"DELETE"})
     */
    public function deleteComment($id, Request $request, CommentRepository $commentRepository, EntityManagerInterface $entityManager): Response
    {
        // Perform your delete operation here. For example:
         $comment = $commentRepository->find($id);
         if ($comment) {
             $entityManager->remove($comment);
             $entityManager->flush();

             return $this->json(['message' => 'Comment deleted successfully.']);
         }

        // Return an error response if the comment wasn't found or if the deletion failed
        return $this->json(['error' => 'Comment not found'], Response::HTTP_NOT_FOUND);
    }
}
