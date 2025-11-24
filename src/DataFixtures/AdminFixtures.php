<?php

namespace App\DataFixtures;

use App\Entity\Admin;
use App\Enum\Role;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class AdminFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // Créer un admin de test avec email connu
        $testAdmin = new Admin();
        $testAdmin->setEmail('admin@bilemo.com');
        $testAdmin->setPasswordHash(password_hash('admin123', PASSWORD_BCRYPT));
        $testAdmin->setRole(Role::ADMIN);
        $manager->persist($testAdmin);

        // Créer 3 admins supplémentaires
        for ($i = 0; $i < 3; $i++) {
            $admin = new Admin();
            $admin->setEmail($faker->unique()->safeEmail());
            $admin->setPasswordHash(password_hash('password123', PASSWORD_BCRYPT));
            $admin->setRole(Role::ADMIN);

            $manager->persist($admin);
        }

        $manager->flush();
    }
}
