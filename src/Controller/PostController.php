<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Comment;
use App\Entity\Post;
use App\Form\CommentType;
use App\Form\PostType;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use App\Services\PostService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PostController extends AbstractController
{
    /**
     * @Route("/", name="app_post")
     */
    public function index(PostRepository $posts): Response
    {
        return $this->render('post/index.html.twig', ['posts' => $posts->findAllWithComments()]);
    }

    /**
     * @Route("/posts", name="delete_comment", methods={"GET"})
     */
    public function getPosts(
        Request $request,
        PostRepository $postRepository,
        PaginatorInterface $paginator
    ): Response
    {
        $user = $this->getUser();
        $userId = $user ? $user->getId() : null;

        $title = $request->query->get('title', '');

        // Query to get all posts (or you might modify it to get a QueryBuilder instance)
        $queryBuilder = $postRepository->findAllWithCommentCountQueryBuilder($userId);

        if (!empty($title)) {
            $queryBuilder->where('p.title LIKE :title')->setParameter('title', '%' . $title . '%');
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
            'total' => $pagination->getTotalItemCount(),
            'page' => $currentPage,
            'limit' => $limit,
        ];

        return $this->json($responseData, Response::HTTP_OK);

        // Return an error response if the comment wasn't found or if the deletion failed
//        return $this->json(['error' => 'Comment not found'], Response::HTTP_NOT_FOUND);
    }

    /**
     * @Route("/post/top-liked", name="app_post_topliked")
     */
    public function topLiked(PostRepository $posts): Response
    {
        return $this->render('post/top_liked.html.twig', ['posts' => $posts]); // $posts->findAllWithMinLikes(2)
    }

    /**
     * @Route("/post/follows", name="app_post_follows")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function follows(PostRepository $posts): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        return $this->render('post/follows.html.twig', ['posts' => $posts]); // $posts->findAllByAuthors($currentUser->getFollows())
    }

    /**
     * @Route("/post/{slug}", name="app_post_show")
     */
    public function showOne(
        string $slug, Request $request, PostRepository $postRepository, CommentRepository $commentRepository
    ): Response // * @IsGranted("VIEW", subject="post")
    {
        $post = $postRepository->findOneBy(['slug' => $slug]);

        if (!$post) {
            throw $this->createNotFoundException('No post found for slug '.$slug);
        }

        $commentForm = $this->createForm(CommentType::class, new Comment());

        $commentForm->handleRequest($request);

        if ($commentForm->isSubmitted() && $commentForm->isValid()) {
            $comment = $commentForm->getData();
            $comment->setAuthor($this->getUser());
            $comment->setPost($post);
            $commentRepository->add($comment, true);
            $this->addFlash('success', 'Your comment has been added.');
        }

        return $this->render('post/show.html.twig', [
            'post' => $post,
            'commentForm' => $commentForm->createView()
        ]);
    }

    /**
     * @Route("/post/add", name="app_post_add", priority=2)
     */
    public function add(Request $request, PostRepository $posts, PostService $postService): Response // * @IsGranted("ROLE_WRITER")
    {
        $form = $this->createForm(PostType::class, new Post());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post = $form->getData();
            $post->setUser($this->getUser());
            $slug = $postService->generateSlug($post->getTitle());
            $post->setSlug($slug);
            $posts->add($post, true);
            $this->addFlash('success', 'Your post has been added.');
            return $this->redirectToRoute('app_post');
        }

        return $this->renderForm('post/add.html.twig', ['form' => $form]);
    }

    /**
     * @Route("/post/{post}/edit", name="app_post_edit")
     */
    public function edit(Post $post, Request $request, PostRepository $posts): Response // * @IsGranted("EDIT", subject="post")
    {
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $posts->add($form->getData(), true);
            # TODO: ADD SLUG
            $this->addFlash('success', 'Your post has been updated.');
            return $this->redirectToRoute('app_post');
        }

        return $this->renderForm('post/edit.html.twig', ['form' => $form, 'post' => $post]);
    }

    /**
     * @Route("/post/{post}/comment", name="app_post_comment")
     */
    public function addComment(Post $post, Request $request, CommentRepository $comments): Response // * @IsGranted("ROLE_COMMENTER")
    {
        $form = $this->createForm(CommentType::class, new Comment());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment = $form->getData();
            $comment->setPost($post);
            $comment->setAuthor($this->getUser());
            $comments->add($comment, true);
            $this->addFlash('success', 'Your comment has been posted.');

            return $this->redirectToRoute('app_post_show', ['slug' => $post->getSlug()]);
        }

        return $this->renderForm('post/comment.html.twig', ['form' => $form, 'post' => $post]);
    }
}
