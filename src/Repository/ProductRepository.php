<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * Trouve les produits de manière paginée avec tri
     * 
     * @param int $offset Le décalage (offset) pour la pagination
     * @param int $limit Le nombre de résultats à retourner
     * @param string $sort Le champ sur lequel trier
     * @param string $order L'ordre de tri ('ASC' ou 'DESC')
     * 
     * @return Product[] Les produits correspondants
     */
    public function findPaginated(int $offset, int $limit, string $sort = 'id', string $order = 'ASC'): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.' . $sort, $order)
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Récupère tous les produits disponibles
     * 
     * @return Product[] Les produits disponibles
     */
    public function findAvailable(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.availability = :available')
            ->setParameter('available', true)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Recherche les produits par marque
     * 
     * @return Product[] Les produits correspondant à la marque
     */
    public function findByBrand(string $brand): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.brand = :brand')
            ->setParameter('brand', $brand)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
