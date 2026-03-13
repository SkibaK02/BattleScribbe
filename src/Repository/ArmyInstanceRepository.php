<?php

namespace App\Repository;

use App\Entity\ArmyInstance;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ArmyInstance>
 */
class ArmyInstanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ArmyInstance::class);
    }

    /**
     * @return ArmyInstance[]
     */
    public function findByOwnerAndFaction(User $user, int $factionId): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.owner = :user')
            ->andWhere('IDENTITY(a.faction) = :faction')
            ->setParameter('user', $user)
            ->setParameter('faction', $factionId)
            ->orderBy('a.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
