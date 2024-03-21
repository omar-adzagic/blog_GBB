<?php

namespace App\DataFixtures;

use App\Entity\Post;
use App\Entity\PostTag;
use App\Entity\PostTranslation;
use App\Entity\User;
use App\Repository\TagRepository;
use App\Repository\UserRepository;
use App\Service\ContentTranslationService;
use App\Service\PostManager;
use App\Service\TagManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Yaml\Yaml;

class PostFixtures extends Fixture implements DependentFixtureInterface
{
    private $contentTranslationService;
    private $userRepository;
    private $tagRepository;
    private $postManager;
    private $tagManager;
    private $users;
    private $tags;
    private $env;

    public function __construct(
        ContentTranslationService $contentTranslationService,
        UserRepository $userRepository,
        TagRepository $tagRepository,
        PostManager $postManager,
        TagManager $tagManager,
        ParameterBagInterface $params
    )
    {
        $this->contentTranslationService = $contentTranslationService;
        $this->userRepository = $userRepository;
        $this->tagRepository = $tagRepository;
        $this->postManager = $postManager;
        $this->tagManager = $tagManager;
        $this->env = $params->get('kernel.environment');
    }

    public function load(ObjectManager $manager)
    {
        if ($this->env !== 'dev') {
            return;
        }

        $this->users = $this->userRepository->findAll();
        $this->tags = $this->tagRepository->findAll();

        $postsData = Yaml::parse(file_get_contents(__DIR__ . '\Resources\posts_data.yaml'));

        foreach ($postsData as $postData) {
            $post = $this->createPost($manager, $postData);
            $this->createPostTranslations($manager, $post, $postData);
        }
        $manager->flush();
    }

    public function createPost($manager, $postData): Post
    {
        $post = new Post();
        $post->setTitle(null);
        $post->setContent(null);
        $slug = $this->postManager->generateSlug($postData['en']['title']);
        $post->setSlug($slug);
        $post->setImage($postData['image']);
        $this->setRandomIsPublished($post);
        $randomUser = $this->getRandomUser();
        $post->setUser($randomUser);
        $this->setRandomTagsToPost($manager, $post, $randomUser);
        $manager->persist($post);
        $manager->flush();
        return $post;
    }

    public function createPostTranslations($manager, $post, $postData)
    {
        foreach($this->contentTranslationService->getSupportedLocales() as $locale) {
            $titleTranslation = new PostTranslation($locale, 'title', $postData[$locale]['title']);
            $titleTranslation->setObject($post);
            $manager->persist($titleTranslation);
            $contentTranslation = new PostTranslation($locale, 'content', $postData[$locale]['content']);
            $contentTranslation->setObject($post);
            $manager->persist($contentTranslation);
        }
    }

    public function createPostTag($post, $tag, $user): PostTag
    {
        $postTag = new PostTag();
        $postTag->setPost($post);
        $postTag->setTag($tag);
        $postTag->setCreatedBy($user);
        $post->addPostTag($postTag);
        return $postTag;
    }

    public function getRandomUser(): User
    {
        $randomUserKey = array_rand($this->users);
        return $this->users[$randomUserKey];
    }

    public function setRandomTagsToPost($manager, $post, $user)
    {
        $randNumOfTags = min(mt_rand(1, 3), count($this->tags));
        if ($randNumOfTags > 0 && count($this->tags) > 0) {
            shuffle($this->tags);
            $randomTags = array_slice($this->tags, 0, $randNumOfTags);
            foreach ($randomTags as $tag) {
                $manager->persist($tag);
                $postTag = $this->createPostTag($post, $tag, $user);
                $manager->persist($postTag);
            }
        }
    }

    public function setRandomIsPublished($post): void
    {
        $randomNumber = mt_rand(1, 5);
        $post->setIsPublished($randomNumber !== 1);
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            TagFixtures::class,
        ];
    }
}
