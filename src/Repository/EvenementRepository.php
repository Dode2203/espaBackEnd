<?php

namespace App\Repository;

use App\Entity\Evenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;
use App\Entity\Utilisateur;
/**
 * @extends ServiceEntityRepository<Evenement>
 */
class EvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evenement::class);
    }
    public function getById(Uuid $id): ?Evenement
    {
        return $this->find($id);
    }

    //    /**
    //     * @return Evenement[] Returns an array of Evenement objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('e.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Evenement
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function findEvenementsBeforeDate(\DateTimeInterface $date, int $limit = 10, Utilisateur $user = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->andWhere('e.datePublication < :date')
            ->setParameter('date', $date)
            ->orderBy('e.datePublication', 'DESC')
            ->setMaxResults($limit);

        if ($user && $user->getRole()->getName() !== 'Admin') {
            $qb->andWhere('e.utilisateur = :user')
            ->setParameter('user', $user);
        }

        return $qb->getQuery()->getResult();
    }


}   
