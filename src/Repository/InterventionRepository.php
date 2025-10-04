<?php

namespace App\Repository;

use App\Entity\Intervention;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Intervention>
 *
 * @method Intervention|null find($id, $lockMode = null, $lockVersion = null)
 * @method Intervention|null findOneBy(array $criteria, array $orderBy = null)
 * @method Intervention[]    findAll()
 * @method Intervention[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InterventionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Intervention::class);
    }

    /**
     * @return Intervention[] Returns an array of Intervention objects
     */
    public function findByFilters($maintenance, $dateDebut, $dateFin): array
    {
        $qb = $this->createQueryBuilder('i');

        if ($maintenance) {
            $qb->innerJoin('i.maintenance', 'm')
               ->andWhere('m.titre LIKE :maintenance')
               ->setParameter('maintenance', '%' . $maintenance . '%');
        }

        if ($dateDebut) {
            $qb->andWhere('i.dateDebut >= :dateDebut')
               ->setParameter('dateDebut', $dateDebut);
        }

        if ($dateFin) {
            $qb->andWhere('i.dateFin <= :dateFin')
               ->setParameter('dateFin', $dateFin);
        }

        return $qb->orderBy('i.id', 'ASC')->getQuery()->getResult();
    }
}
