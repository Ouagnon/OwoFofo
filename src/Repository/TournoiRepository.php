<?php

namespace App\Repository;

use App\Entity\Joueur;
use App\Entity\Tournoi;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tournoi>
 */
class TournoiRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tournoi::class);
    }

    /**
     * @return Tournoi[]
     */
    public function findPublicAccueil(?string $recherche, int $page = 1, int $limite = 40): array
    {
        $page = max(1, $page);
        $limite = max(1, min(100, $limite));

        $qb = $this->createQueryBuilder('t')
            ->andWhere('t.brouillon = :brouillon')
            ->setParameter('brouillon', false)
            ->orderBy('t.createdAt', 'DESC');

        $recherche = trim((string) $recherche);
        if ($recherche !== '') {
            $qb
                ->andWhere('LOWER(t.nom) LIKE :motCle OR LOWER(t.description) LIKE :motCle')
                ->setParameter('motCle', '%'.mb_strtolower($recherche).'%');
        }

        $qb
            ->setFirstResult(($page - 1) * $limite)
            ->setMaxResults($limite);

        return $qb->getQuery()->getResult();
    }

    public function countPublicAccueil(?string $recherche): int
    {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.brouillon = :brouillon')
            ->setParameter('brouillon', false);

        $recherche = trim((string) $recherche);
        if ($recherche !== '') {
            $qb
                ->andWhere('LOWER(t.nom) LIKE :motCle OR LOWER(t.description) LIKE :motCle')
                ->setParameter('motCle', '%'.mb_strtolower($recherche).'%');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return Tournoi[]
     */
    public function findParCreateur(Joueur $joueur): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.createur = :joueur')
            ->setParameter('joueur', $joueur)
            ->orderBy('t.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Tournoi[]
     */
    public function findBrouillonsParCreateur(Joueur $joueur): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.createur = :joueur')
            ->andWhere('t.brouillon = :brouillon')
            ->setParameter('joueur', $joueur)
            ->setParameter('brouillon', true)
            ->orderBy('t.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Tournoi[]
     */
    public function findPubliesParCreateur(Joueur $joueur): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.createur = :joueur')
            ->andWhere('t.brouillon = :brouillon')
            ->setParameter('joueur', $joueur)
            ->setParameter('brouillon', false)
            ->orderBy('t.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
