<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Comment;
use App\Entity\Post;
use App\Form\CommentType;
use App\Form\PostType;
use App\Message\SendEmailMessage;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use App\Service\FileUploader;
use App\Service\PostManager;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use App\Security\EmailVerifier;

class PostController extends AbstractController
{
    /**
     * @Route("/", name="app_post")
     */
    public function index(PostRepository $postRepository): Response
    {
        $posts = $postRepository->findPublishedWithComments();
        return $this->render('post/index.html.twig', ['posts' => $posts]);
    }

    /**
     * @Route("/posts", name="delete_post_comment", methods={"GET"})
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

        // Return an error response if the comment wasn't found or if the deletion failed
//        return $this->json(['error' => 'Comment not found'], Response::HTTP_NOT_FOUND);
    }

    /**
     * @Route("/post/{slug}", name="app_post_show")
     */
    public function showOne(
        string $slug,
        Request $request,
        PostRepository $postRepository,
        CommentRepository $commentRepository,
        UserRepository $userRepository,
        MessageBusInterface $messageBus
    ): Response
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

            $userAdmin = $userRepository->findFirstAdmin();
            if ($userAdmin) {
                $email = (new TemplatedEmail())
                    ->from(new Address('getByBus@test.com', 'Blog GBB'))
                    ->to($userAdmin->getEmail())
                    ->subject('New Comment was Made')
                    ->htmlTemplate('email/new_comment.html.twig')
                    ->context([
                        'admin' => $userAdmin,
                        'comment' => $comment,
                        'post' => $post,
                    ]);

//                $mailer->send($email);
                $messageBus->dispatch(new SendEmailMessage($email));
            }
        }

        return $this->render('post/show.html.twig', [
            'post' => $post,
            'commentForm' => $commentForm->createView(),
        ]);
    }

    /**
     * @Route("/post/create", name="app_post_create", priority=2)
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function create(
        Request $request,
        PostRepository $posts,
        PostManager $postManager,
        FileUploader $fileUploader
    ): Response
    {
        $newPost = new Post();
        $form = $this->createForm(PostType::class, $newPost);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post = $form->getData();

            $postImageFile = $form->get('image')->getData();

            if ($postImageFile) {
                $newFileName = $fileUploader->upload($postImageFile, 'post_images/');
                $post->setImage($newFileName);
            }

            $post->setUser($this->getUser());
            $slug = $postManager->generateSlug($post->getTitle());
            $post->setSlug($slug);
            $posts->add($post, true);

            $this->addFlash('success', 'Your post has been added.');

            return $this->redirectToRoute('app_post');
        }

        $responseData = [
            'form' => $form,
            'post' => $newPost,
        ];
        return $this->renderForm('post/create.html.twig', $responseData);
    }

    /**
     * @Route("/post/{post}/edit", name="app_post_edit")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function edit(
        Post $post, Request $request, PostRepository $posts,
        EntityManagerInterface $entityManager,
        PostManager $postManager,
        FileUploader $fileUploader
    ): Response // * @IsGranted("EDIT", subject="post")
    {
        $authUserId = $this->getUser()->getId();

        if ($post->getUser()->getId() !== $authUserId) {
            // Throw an AccessDeniedException
            throw new AccessDeniedException('You do not have permission to edit this post.');
        }

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $postImageFile = $form->get('image')->getData();

            if ($postImageFile) {
                $oldFilename = $post->getImage();
                if ($oldFilename) {
                    $fileUploader->deleteFile($oldFilename, '/post_images');
                }

                $newFileName = $fileUploader->upload($postImageFile, '/post_images');
                $post->setImage($newFileName);
            }

            // Set slug before persisting entity changes
            $slug = $postManager->generateSlug($post->getTitle());
            $post->setSlug($slug);

            // If $posts->add() method already handles persisting and flushing,
            // there's no need to call $entityManager->persist() and flush() again.
            $posts->add($post, true);

            $this->addFlash('success', 'Your post has been updated.');
            return $this->redirectToRoute('app_post');
        }

        return $this->renderForm('post/edit.html.twig', ['form' => $form, 'post' => $post]);
    }

    /**
     * @Route("/post/{post}/comment", name="app_post_comment")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
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
