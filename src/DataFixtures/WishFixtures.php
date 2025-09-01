<?php

namespace App\DataFixtures;

use App\Entity\Wish;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory as FakerFactory;

class WishFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = FakerFactory::create('fr_FR');

        for ($i = 0; $i < 3; $i++) {
            $wish = new Wish();
            $wish
                ->setTitle($faker->unique()->realText(200))
                ->setDescription($faker->optional()->paragraphs(random_int(1, 3), true))
                ->setAuthor($faker->name())
                ->setIsPublished(true)
                ->setDateCreated($faker->dateTimeBetween('-1 year', 'now'))
            ;

            $manager->persist($wish);
        }

        $manager->flush();
    }
}
