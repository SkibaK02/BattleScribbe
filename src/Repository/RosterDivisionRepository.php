<?php

namespace App\Repository;

use App\Entity\RosterDivision;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RosterDivision>
 */
class RosterDivisionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RosterDivision::class);
    }

    /**
     * @return RosterDivision[]
     */
    public function findByOwnerAndDivision(User $user, int $divisionId): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.owner = :user')
            ->andWhere('IDENTITY(r.division) = :division')
            ->setParameter('user', $user)
            ->setParameter('division', $divisionId)
            ->orderBy('r.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return RosterDivision[]
     */
    public function findByOwnerAndFaction(User $user, int $factionId): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.division', 'd')
            ->andWhere('r.owner = :user')
            ->andWhere('IDENTITY(d.faction) = :faction')
            ->setParameter('user', $user)
            ->setParameter('faction', $factionId)
            ->orderBy('r.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
