<?php

namespace App\DataFixtures;

use App\Entity\Client;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class ClientFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // Créer 10 clients
        for ($i = 0; $i < 10; $i++) {
            $client = new Client();
            $client->setName($faker->company());
            $client->setEmail($faker->unique()->companyEmail());
            $client->setPasswordHash(password_hash('password123', PASSWORD_BCRYPT));

            // Ajouter 2 à 5 utilisateurs par client
            $usersCount = $faker->numberBetween(2, 5);
            for ($j = 0; $j < $usersCount; $j++) {
                $user = new User();
                $user->setFirstname($faker->firstName());
                $user->setLastname($faker->lastName());
                $user->setEmail($faker->unique()->safeEmail());
                $user->setPhone($faker->phoneNumber());
                $user->setClient($client);

                $manager->persist($user);
            }

            $manager->persist($client);
        }

        $manager->flush();
    }
}
