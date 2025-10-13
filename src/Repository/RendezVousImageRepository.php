<?php

namespace App\Repository;

use App\Entity\RendezvousImage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RendezvousImage>
 *
 * @method RendezvousImage|null find($id, $lockMode = null, $lockVersion = null)
 * @method RendezvousImage|null findOneBy(array $criteria, array $orderBy = null)
 * @method RendezvousImage[]    findAll()
 * @method RendezvousImage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RendezvousImageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RendezvousImage::class);
    }
}
