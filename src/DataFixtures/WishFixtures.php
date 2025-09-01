<?php

namespace App\DataFixtures;

use App\Entity\Wish;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory as FakerFactory;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class WishFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = FakerFactory::create('fr_FR');

        $categoryRefs = [
            CategoryFixtures::CAT_TRAVEL,
            CategoryFixtures::CAT_SPORT,
            CategoryFixtures::CAT_ENTERTAINMENT,
            CategoryFixtures::CAT_HUMAN_RELATIONS,
            CategoryFixtures::CAT_OTHERS,
        ];

        for ($i = 0; $i < 3; $i++) {
            $wish = new Wish();
            $wish
                ->setTitle($faker->unique()->realText(200))
                ->setDescription($faker->optional()->paragraphs(random_int(1, 3), true))
                ->setAuthor($faker->name())
                ->setIsPublished(true)
                ->setDateCreated($faker->dateTimeBetween('-1 year', 'now'))
                ->setCategory($this->getReference($faker->randomElement($categoryRefs)))
            ;

            $manager->persist($wish);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [CategoryFixtures::class];
    }
}
