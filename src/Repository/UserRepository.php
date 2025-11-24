<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Récupère tous les utilisateurs d'un client
     * 
     * @return User[] Les utilisateurs du client
     */
    public function findByClient(int $clientId): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.client = :clientId')
            ->setParameter('clientId', $clientId)
            ->orderBy('u.lastname', 'ASC')
            ->addOrderBy('u.firstname', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
