<?php

namespace App\DataFixtures;

use App\Entity\Tag;
use App\Entity\TagTranslation;
use App\Service\ContentTranslationService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Yaml\Yaml;

class TagFixtures extends Fixture
{
    private $contentTranslationService;
        private $env;
    public function __construct(
        ContentTranslationService $contentTranslationService,
        ParameterBagInterface $params
    )
    {
        $this->contentTranslationService = $contentTranslationService;
        $this->env = $params->get('kernel.environment');
    }

    public function load(ObjectManager $manager)
    {
        if ($this->env !== 'dev') {
            return;
        }
        $tagsData = Yaml::parse(file_get_contents(__DIR__ . '\Resources\tags_data.yaml'));

        foreach ($tagsData as $tagData) {
            $tag = new Tag();
            $tag->setName(null);
            $manager->persist($tag);
            $manager->flush();
            foreach ($this->contentTranslationService->getSupportedLocales() as $locale) {
                $entityLocaleTranslation = new TagTranslation(
                    $locale, 'name', $tagData[$locale]['content']
                );
                $entityLocaleTranslation->setObject($tag);
                $manager->persist($entityLocaleTranslation);
            }
            $manager->flush();
        }
    }
}
