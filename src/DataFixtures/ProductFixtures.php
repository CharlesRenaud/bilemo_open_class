<?php

namespace App\DataFixtures;

use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class ProductFixtures extends Fixture
{
    private const BRANDS = ['Apple', 'Samsung', 'Google', 'OnePlus', 'Xiaomi', 'Motorola', 'Nokia', 'Sony'];
    private const MODELS = ['Pro Max', 'Ultra', 'Pro', 'Lite', 'Plus', 'Standard', 'Air', 'Edge'];

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // Créer 20 produits (téléphones)
        for ($i = 0; $i < 20; $i++) {
            $product = new Product();
            $brand = $faker->randomElement(self::BRANDS);
            $model = $faker->randomElement(self::MODELS);

            $product->setName($brand . ' ' . $model . ' ' . $faker->numberBetween(2023, 2025));
            $product->setBrand($brand);
            $product->setModel($model);
            $product->setPrice((string)$faker->numberBetween(299, 1500) . '.99');
            $product->setDescription($faker->sentence(10));
            $product->setImageUrl($faker->imageUrl(400, 400, 'smartphone', true));
            $product->setAvailability($faker->boolean(80)); // 80% de chance d'être disponible

            $manager->persist($product);
        }

        $manager->flush();
    }
}
