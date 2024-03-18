<?php

namespace App\Controller\Api;

use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\User;
use App\Form\CommentType;
use App\Form\PostType;
use App\Message\SendEmailMessage;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use App\Repository\UserProfileRepository;
use App\Repository\UserRepository;
use App\Service\FileUploader;
use App\Service\PostManager;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class PostController extends AbstractController
{
    /**
     * @Route("/posts", name="delete_post_comment", methods={"GET"})
     */
    public function getPosts(
        Request $request,
        UserRepository $userRepository,
        PostRepository $postRepository,
        PaginatorInterface $paginator
    ): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $userId = $user ? $user->getId() : null;

        $title = $request->query->get('title', '');

        // Query to get all posts (or you might modify it to get a QueryBuilder instance)
        $queryBuilder = $postRepository->findAllWithCommentCountQueryBuilder($userId);

        // Ensure that only published posts are considered.
        $queryBuilder->where('p.is_published = 1');

        // Add a conditional search by title if it's provided
        if (!empty($title)) {
            // Use andWhere to add to the existing where condition
            $queryBuilder->andWhere('p.title LIKE :title')->setParameter('title', '%' . $title . '%');
        }

        // Get current page from query parameters, default is 1 if not set
        $currentPage = $request->query->getInt('page', 1);
        // Get limit from query parameters, default is 6 if not set
        $limit = $request->query->getInt('limit', 6);

        // Paginate the results of the query
        $pagination = $paginator->paginate(
            $queryBuilder, /* query NOT result */
            $currentPage, /* page number */
            $limit /* limit per page */
        );

        $posts = $pagination->getItems();

        $responseData = [
            'posts' => $posts,
            'is_authenticated' => $this->isGranted('IS_AUTHENTICATED_REMEMBERED'),
            'auth_id' => $userId,
            'total' => $pagination->getTotalItemCount(),
            'page' => $currentPage,
            'limit' => $limit,
        ];

        return $this->json($responseData, Response::HTTP_OK);
    }
}
