<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Tag;

class TagFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $tags = [
            'JavaScript', 'HTML5', 'CSS3', 'React', 'Angular', 'Vue.js',
            'Node.js', 'Django', 'Git', 'Docker', 'Agile Development',
            'TDD', 'Responsive Design', 'AI'
        ];

        foreach ($tags as $tagName) {
            $tag = new Tag();
            $tag->setName($tagName);
            $manager->persist($tag);
        }

        $manager->flush();
    }
}
