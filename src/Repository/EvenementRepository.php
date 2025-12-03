<?php

namespace App\Repository;

use App\Entity\Evenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;
use App\Entity\Utilisateur;
use App\Entity\TypeEvent;
/**
 * @extends ServiceEntityRepository<Evenement>
 */
class EvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evenement::class);
    }

    public function getById(string $id): ?Evenement
    {
        // Vérifie que la chaîne est un UUID valide
        if (!Uuid::isValid($id)) {
            throw new \InvalidArgumentException("UUID invalide : $id");
        }

        // Convertit en objet Uuid
        $uuid = Uuid::fromString($id);

        return $this->find($uuid);
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

    public function findEvenementsBeforeDate(\DateTimeInterface $date, int $limit = 10, Utilisateur $user = null,TypeEvent $typeEvent = null): array
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
        if ($typeEvent !== null) {
            $qb->andWhere('e.typeEvent = :typeEvent')
                ->setParameter('typeEvent', $typeEvent);
        }

        return $qb->getQuery()->getResult();
    }


}   
