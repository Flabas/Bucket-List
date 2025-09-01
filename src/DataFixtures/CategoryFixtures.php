<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CategoryFixtures extends Fixture
{
    public const CAT_TRAVEL = 'cat_travel';
    public const CAT_SPORT = 'cat_sport';
    public const CAT_ENTERTAINMENT = 'cat_entertainment';
    public const CAT_HUMAN_RELATIONS = 'cat_human_relations';
    public const CAT_OTHERS = 'cat_others';

    public function load(ObjectManager $manager): void
    {
        $names = [
            self::CAT_TRAVEL => 'Travel & Adventure',
            self::CAT_SPORT => 'Sport',
            self::CAT_ENTERTAINMENT => 'Entertainment',
            self::CAT_HUMAN_RELATIONS => 'Human Relations',
            self::CAT_OTHERS => 'Others',
        ];

        foreach ($names as $ref => $name) {
            $category = (new Category())->setName($name);
            $manager->persist($category);
            $this->addReference($ref, $category);
        }

        $manager->flush();
    }
}

