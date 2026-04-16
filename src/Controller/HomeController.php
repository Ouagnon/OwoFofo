<?php

namespace App\Controller;

use App\Entity\Joueur;
use App\Repository\PartieRepository;
use App\Repository\TournoiRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HomeController extends AbstractController
{
    /**
     * Affiche la page d'accueil avec recherche, pagination et reprise de parties actives.
     */
    public function accueil(
        Request $request,
        TournoiRepository $tournoiRepository,
        PartieRepository $partieRepository
    ): Response
    {
        $recherche = trim((string) $request->query->get('q', ''));
        $page = max(1, (int) $request->query->get('page', 1));
        $parPage = 40;

        $totalTournois = $tournoiRepository->countPublicAccueil($recherche);
        $totalPages = max(1, (int) ceil($totalTournois / $parPage));

        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $tournois = $tournoiRepository->findPublicAccueil($recherche, $page, $parPage);

        $partiesActivesParTournoi = [];
        $joueur = $this->getUser();
        if ($joueur instanceof Joueur) {
            foreach ($partieRepository->findPartiesActivesParJoueur($joueur) as $partieActive) {
                $tournoiId = $partieActive->getTournoi()?->getId();
                if ($tournoiId === null || isset($partiesActivesParTournoi[$tournoiId])) {
                    continue;
                }

                $partiesActivesParTournoi[$tournoiId] = $partieActive;
            }
        }

        return $this->render('home/index.html.twig', [
            'recherche' => $recherche,
            'tournois' => $tournois,
            'pageCourante' => $page,
            'totalPages' => $totalPages,
            'totalTournois' => $totalTournois,
            'partiesActivesParTournoi' => $partiesActivesParTournoi,
        ]);
    }
}
