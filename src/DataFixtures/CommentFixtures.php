<?php

namespace App\DataFixtures;

use App\Entity\Comment;
use App\Entity\User;
use App\Entity\Wish;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory as FakerFactory;

class CommentFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = FakerFactory::create('fr_FR');

        $wishes = $manager->getRepository(Wish::class)->findAll();
        $users = $manager->getRepository(User::class)->findAll();
        if (!$wishes || !$users) {
            return; // rien à faire si pas de données
        }

        foreach ($wishes as $wish) {
            $count = $faker->numberBetween(0, 4);
            for ($i = 0; $i < $count; $i++) {
                // Choisir un user différent de l'auteur du souhait si possible
                $user = $faker->randomElement($users);
                if ($wish->getAuthor() && $user->getPseudo() === $wish->getAuthor() && count($users) > 1) {
                    // prendre un autre utilisateur
                    $pool = array_filter($users, fn(User $u) => $u->getPseudo() !== $wish->getAuthor());
                    if (!empty($pool)) {
                        $user = $faker->randomElement($pool);
                    }
                }

                $comment = new Comment();
                $comment->setWish($wish);
                $comment->setAuthor($user);
                $comment->setRating($faker->numberBetween(1, 5));
                $comment->setContent($faker->sentences($faker->numberBetween(1, 3), true));

                $manager->persist($comment);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            WishFixtures::class,
        ];
    }
}

