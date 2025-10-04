<?php

namespace App\Repository;

use App\Entity\Rendezvous;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Rendezvous>
 *
 * @method Rendezvous|null find($id, $lockMode = null, $lockVersion = null)
 * @method Rendezvous|null findOneBy(array $criteria, array $orderBy = null)
 * @method Rendezvous[]    findAll()
 * @method Rendezvous[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RendezvousRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rendezvous::class);
    }
}
