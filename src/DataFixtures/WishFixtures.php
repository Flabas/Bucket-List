<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Wish;
use App\Entity\User;
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

        // Récupérer les utilisateurs pour choisir un auteur existant
        $users = $manager->getRepository(User::class)->findAll();
        if (count($users) === 0) {
            // Pas d'utilisateurs: on ne crée pas de souhaits pour éviter des auteurs incohérents
            return;
        }

        for ($i = 0; $i < 20; $i++) {
            $user = $faker->randomElement($users);

            $wish = new Wish();
            $wish
                ->setTitle($faker->unique()->sentence(random_int(3, 6)))
                ->setDescription($faker->paragraphs(1, true))
                ->setAuthor($user->getPseudo())
                ->setImage('snowfall.jpg')
                ->setIsPublished(true)
                ->setDateCreated($faker->dateTimeBetween('-1 year', 'now'))
                ->setCategory($this->getReference($faker->randomElement($categoryRefs), Category::class))
            ;

            $manager->persist($wish);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            CategoryFixtures::class,
            UserFixtures::class,
        ];
    }
}
