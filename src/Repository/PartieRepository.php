<?php

namespace App\Repository;

use App\Entity\EtatPartie;
use App\Entity\Joueur;
use App\Entity\Tournoi;
use App\Entity\Partie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Partie>
 */
class PartieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Partie::class);
    }

    public function findPartieActivePourJoueurEtTournoi(Joueur $joueur, Tournoi $tournoi): ?Partie
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.joueur = :joueur')
            ->andWhere('p.tournoi = :tournoi')
            ->andWhere('p.etat != :etatTerminee')
            ->setParameter('joueur', $joueur)
            ->setParameter('tournoi', $tournoi)
            ->setParameter('etatTerminee', EtatPartie::TERMINEE)
            ->orderBy('p.updatedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Partie[]
     */
    public function findPartiesActivesParJoueur(Joueur $joueur): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.joueur = :joueur')
            ->andWhere('p.etat != :etatTerminee')
            ->setParameter('joueur', $joueur)
            ->setParameter('etatTerminee', EtatPartie::TERMINEE)
            ->orderBy('p.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Partie[]
     */
    public function findPartiesTermineesParTournoi(Tournoi $tournoi): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.tournoi = :tournoi')
            ->andWhere('p.etat = :etatTerminee')
            ->setParameter('tournoi', $tournoi)
            ->setParameter('etatTerminee', EtatPartie::TERMINEE)
            ->orderBy('p.finishedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Parties[] Returns an array of Parties objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Parties
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
