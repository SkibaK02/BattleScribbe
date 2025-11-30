<?php

namespace App\Repository;

use App\Entity\UnitBuild;
use App\Entity\User;
use App\Entity\Division;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UnitBuild>
 */
class UnitBuildRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UnitBuild::class);
    }

    public function save(UnitBuild $entity, bool $flush = false): void
    {
        $this->_em->persist($entity);

        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @return UnitBuild[]
     */
    public function findByOwnerAndDivision(User $owner, Division $division): array
    {
        return $this->createQueryBuilder('b')
            ->join('b.unitTemplate', 't')
            ->andWhere('b.owner = :owner')
            ->andWhere('t.division = :division')
            ->setParameter('owner', $owner)
            ->setParameter('division', $division)
            ->orderBy('t.name', 'ASC')
            ->addOrderBy('b.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

