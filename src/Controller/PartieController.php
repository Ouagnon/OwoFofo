<?php

namespace App\Controller;

use App\Entity\ActionImpair;
use App\Entity\ClassementPerdant;
use App\Entity\DecisionImpair;
use App\Entity\Duel;
use App\Entity\Element;
use App\Entity\EtatDuel;
use App\Entity\EtatManche;
use App\Entity\EtatPartie;
use App\Entity\Joueur;
use App\Entity\Manche;
use App\Entity\ModeAppariement;
use App\Entity\ModeTournoi;
use App\Entity\Partie;
use App\Entity\Repechage;
use App\Entity\Tournoi;
use App\Entity\TypeConfrontation;
use App\Entity\TypeManche;
use App\Repository\PartieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class PartieController extends AbstractController
{
    private const MIN_TAILLE = 8;

    /**
     * Route HTTP: point d'entrée utilisateur.
     */
    #[Route('/tournois/{id}/jouer', name: 'app_partie_config', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function configurer(
        Tournoi $tournoi,
        Request $request,
        PartieRepository $partieRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $joueur = $this->getUser();
        if (!($joueur instanceof Joueur)) {
            throw $this->createAccessDeniedException('Session utilisateur invalide.');
        }

        if ($tournoi->isBrouillon()) {
            throw $this->createNotFoundException('Ce tournoi n\'est pas public.');
        }

        $pool = $this->analyserPoolTournoi($tournoi);
        $taillesDisponibles = $this->calculerTaillesDisponibles($tournoi, $pool);
        $afficherToutUtiliser = $this->doitAfficherOptionToutUtiliser($tournoi, $pool);
        $partieActive = $partieRepository->findPartieActivePourJoueurEtTournoi($joueur, $tournoi);

        if ($request->isMethod('POST')) {
            $action = (string) $request->request->get('action', 'start');

            if ($action === 'resume' && $partieActive instanceof Partie) {
                return $this->redirectToRoute('app_partie_play', ['id' => $partieActive->getId()]);
            }

            if ($action === 'restart' && $partieActive instanceof Partie) {
                $entityManager->remove($partieActive);
                $entityManager->flush();
                $this->effacerOptionsPartie($request, (int) $partieActive->getId());
                $this->addFlash('success', 'Partie précédente supprimée.');

                return $this->redirectToRoute('app_partie_config', ['id' => $tournoi->getId()]);
            }

            if ($action === 'start' && $partieActive instanceof Partie) {
                $entityManager->remove($partieActive);
                $entityManager->flush();
                $this->effacerOptionsPartie($request, (int) $partieActive->getId());
                $partieActive = null;
            }

            $utiliserTous = $request->request->getBoolean('utiliser_tous');
            if (!$afficherToutUtiliser) {
                $utiliserTous = false;
            }

            $tailleDemandee = (int) $request->request->get('taille', 0);
            $activerRepechage = $request->request->getBoolean('activer_repechage');
            $activerClassement = $request->request->getBoolean('activer_classement');

            if (!$utiliserTous && !in_array($tailleDemandee, $taillesDisponibles, true)) {
                $this->addFlash('danger', 'Nombre d\'éléments invalide pour cette partie.');

                return $this->redirectToRoute('app_partie_config', ['id' => $tournoi->getId()]);
            }

            try {
                $elements = $this->construireParticipantsInitial(
                    $tournoi,
                    $pool,
                    $utiliserTous,
                    $tailleDemandee
                );
            } catch (\InvalidArgumentException $exception) {
                $this->addFlash('danger', $exception->getMessage());

                return $this->redirectToRoute('app_partie_config', ['id' => $tournoi->getId()]);
            }

            if (count($elements) < 2) {
                $this->addFlash('danger', 'Pas assez d\'éléments pour lancer une partie.');

                return $this->redirectToRoute('app_partie_config', ['id' => $tournoi->getId()]);
            }

            $partie = new Partie();
            $partie
                ->setJoueur($joueur)
                ->setTournoi($tournoi)
                ->setEtat(EtatPartie::EN_COURS)
                ->setStartedAt(new \DateTimeImmutable())
                ->setFinishedAt(null)
                ->setVainqueurFinal(null);

            $entityManager->persist($partie);
            $this->creerMancheDepuisElements($partie, $elements, 1, $tournoi, $entityManager);
            $entityManager->flush();

            $this->enregistrerOptionsPartie($request, (int) $partie->getId(), [
                'repechage' => $activerRepechage,
                'classement' => $activerClassement,
                'utiliserTous' => $utiliserTous,
            ]);

            return $this->redirectToRoute('app_partie_play', ['id' => $partie->getId()]);
        }

        $desquilibreTheme = $tournoi->getMode() === ModeTournoi::THEME_VS_THEME
            ? abs($pool['compteThemeA'] - $pool['compteThemeB']) > 0
            : false;

        return $this->render('partie/config.html.twig', [
            'tournoi' => $tournoi,
            'partieActive' => $partieActive,
            'taillesDisponibles' => $taillesDisponibles,
            'totalElements' => count($pool['elements']),
            'compteThemeA' => $pool['compteThemeA'],
            'compteThemeB' => $pool['compteThemeB'],
            'desquilibreTheme' => $desquilibreTheme,
            'afficherToutUtiliser' => $afficherToutUtiliser,
            'prefRepechage' => $joueur->isActiverRepechage(),
            'prefClassement' => $joueur->isActiverClassementPerdants(),
        ]);
    }

    /**
     * Route HTTP: point d'entrée utilisateur.
     */
    #[Route('/parties/{id}', name: 'app_partie_play', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function jouer(Partie $partie, Request $request, EntityManagerInterface $entityManager): Response
    {
        $joueur = $this->getUser();
        if (!($joueur instanceof Joueur) || $partie->getJoueur()?->getId() !== $joueur->getId()) {
            throw $this->createAccessDeniedException('Cette partie ne t\'appartient pas.');
        }

        $options = $this->lireOptionsPartie($request, $joueur, $partie);
        $this->avancerProgressionPartie($partie, $entityManager, $options);

        if ($partie->getEtat() === EtatPartie::TERMINEE) {
            return $this->redirectToRoute('app_tournoi_stats', [
                'id' => $partie->getTournoi()?->getId(),
                'partie' => $partie->getId(),
            ]);
        }

        $manche = $this->trouverMancheCourante($partie);
        if (!($manche instanceof Manche)) {
            $this->addFlash('danger', 'Aucune manche active trouvée pour cette partie.');

            return $this->redirectToRoute('app_partie_config', ['id' => $partie->getTournoi()?->getId()]);
        }

        $decisionImpair = $manche->getDecisionImpair();
        $duelActif = $this->trouverDuelActif($manche);
        $repechageActif = $this->trouverRepechageActif($manche);

        $intermanche = null;
        $autoriserRepechageIntermanche = false;
        $autoriserClassementIntermanche = false;
        if (!($duelActif instanceof Duel)
            && !($repechageActif instanceof Repechage)
            && $manche->getEtat() === EtatManche::EN_PREPARATION
        ) {
            $intermanche = $this->construireIntermancheContext($manche);
            $autoriserRepechageIntermanche = $options['repechage'] === true
                && count($intermanche['winners']) > 1
                && count($intermanche['losers']) > 0;
            $autoriserClassementIntermanche = $options['classement'] === true
                && count($intermanche['losers']) > 0;
        }

        $classementManche = [];
        if ($intermanche === null
            && !($duelActif instanceof Duel)
            && !($repechageActif instanceof Repechage)
            && $manche->getClassementsPerdants()->count() > 0
        ) {
            $classementManche = $manche->getClassementsPerdants()->toArray();
            usort($classementManche, static fn (ClassementPerdant $a, ClassementPerdant $b): int => $a->getRang() <=> $b->getRang());
        }

        $candidatsRemplacement = [];
        if ($decisionImpair instanceof DecisionImpair && $decisionImpair->getAction() === ActionImpair::EN_ATTENTE) {
            foreach ($this->collecterQualifiesManche($manche) as $qualifie) {
                $candidatsRemplacement[] = $qualifie;
            }
        }

        return $this->render('partie/play.html.twig', [
            'masquerNavbar' => true,
            'partie' => $partie,
            'manche' => $manche,
            'duelActif' => $duelActif,
            'repechageActif' => $repechageActif,
            'decisionImpair' => $decisionImpair,
            'candidatsRemplacement' => $candidatsRemplacement,
            'intermanche' => $intermanche,
            'autoriserRepechageIntermanche' => $autoriserRepechageIntermanche,
            'autoriserClassementIntermanche' => $autoriserClassementIntermanche,
            'classementManche' => $classementManche,
            'nomTypeManche' => $this->nomTypeManche($manche->getType()),
            'nombreElementsManche' => $this->compterElementsDansManche($manche),
            'nombreDuels' => $manche->getDuels()->count(),
            'nombreRepechages' => $manche->getRepechages()->count(),
            'tournoiModeTheme' => $partie->getTournoi()?->getMode() === ModeTournoi::THEME_VS_THEME,
        ]);
    }

    /**
     * Route HTTP: point d'entrée utilisateur.
     */
    #[Route('/parties/{id}/duels/{duel}/vote/{elementId}', name: 'app_partie_vote_duel', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function voterDuel(
        Partie $partie,
        Duel $duel,
        int $elementId,
        EntityManagerInterface $entityManager
    ): Response {
        $joueur = $this->getUser();
        if (!($joueur instanceof Joueur) || $partie->getJoueur()?->getId() !== $joueur->getId()) {
            throw $this->createAccessDeniedException('Cette partie ne t\'appartient pas.');
        }

        if ($duel->getManche()?->getPartie()?->getId() !== $partie->getId()) {
            throw $this->createAccessDeniedException('Ce duel ne correspond pas à la partie.');
        }

        if ($duel->getEtat() !== EtatDuel::A_JOUER) {
            return $this->redirectToRoute('app_partie_play', ['id' => $partie->getId()]);
        }

        $vainqueur = null;
        if ($duel->getElementA()?->getId() === $elementId) {
            $vainqueur = $duel->getElementA();
        }
        if ($duel->getElementB()?->getId() === $elementId) {
            $vainqueur = $duel->getElementB();
        }

        if (!($vainqueur instanceof Element)) {
            $this->addFlash('danger', 'Choix du vainqueur invalide.');

            return $this->redirectToRoute('app_partie_play', ['id' => $partie->getId()]);
        }

        $duel
            ->setVainqueur($vainqueur)
            ->setEtat(EtatDuel::TERMINE);

        $partie->touch();
        $entityManager->flush();

        return $this->redirectToRoute('app_partie_play', ['id' => $partie->getId()]);
    }

    /**
     * Route HTTP: point d'entrée utilisateur.
     */
    #[Route('/parties/{id}/repechages/{repechage}/vote/{elementId}', name: 'app_partie_vote_repechage', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function voterRepechage(
        Partie $partie,
        Repechage $repechage,
        int $elementId,
        EntityManagerInterface $entityManager
    ): Response {
        $joueur = $this->getUser();
        if (!($joueur instanceof Joueur) || $partie->getJoueur()?->getId() !== $joueur->getId()) {
            throw $this->createAccessDeniedException('Cette partie ne t\'appartient pas.');
        }

        if ($repechage->getManche()?->getPartie()?->getId() !== $partie->getId()) {
            throw $this->createAccessDeniedException('Ce repêchage ne correspond pas à la partie.');
        }

        if ($repechage->getEtat() !== EtatDuel::A_JOUER) {
            return $this->redirectToRoute('app_partie_play', ['id' => $partie->getId()]);
        }

        $vainqueur = null;
        if ($repechage->getPerdant()?->getId() === $elementId) {
            $vainqueur = $repechage->getPerdant();
        }
        if ($repechage->getVainqueurCible()?->getId() === $elementId) {
            $vainqueur = $repechage->getVainqueurCible();
        }

        if (!($vainqueur instanceof Element)) {
            $this->addFlash('danger', 'Choix du vainqueur invalide.');

            return $this->redirectToRoute('app_partie_play', ['id' => $partie->getId()]);
        }

        $repechage
            ->setVainqueurFinal($vainqueur)
            ->setEtat(EtatDuel::TERMINE);

        $partie->touch();
        $entityManager->flush();

        return $this->redirectToRoute('app_partie_play', ['id' => $partie->getId()]);
    }

    /**
     * Route HTTP: point d'entrée utilisateur.
     */
    #[Route('/parties/{id}/intermanche/valider', name: 'app_partie_valider_intermanche', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function validerIntermanche(Partie $partie, Request $request, EntityManagerInterface $entityManager): Response
    {
        $joueur = $this->getUser();
        if (!($joueur instanceof Joueur) || $partie->getJoueur()?->getId() !== $joueur->getId()) {
            throw $this->createAccessDeniedException('Cette partie ne t\'appartient pas.');
        }

        $manche = $this->trouverMancheCourante($partie);
        if (!($manche instanceof Manche)
            || $manche->getEtat() !== EtatManche::EN_PREPARATION
            || $this->trouverDuelActif($manche) instanceof Duel
            || $this->trouverRepechageActif($manche) instanceof Repechage
        ) {
            $this->addFlash('warning', 'Aucune phase inter-manche à valider.');

            return $this->redirectToRoute('app_partie_play', ['id' => $partie->getId()]);
        }

        $options = $this->lireOptionsPartie($request, $joueur, $partie);
        $context = $this->construireIntermancheContext($manche);
        $winners = $context['winners'];
        $losers = $context['losers'];

        $autoriserRepechage = $options['repechage'] === true && count($winners) > 1 && count($losers) > 0;
        $autoriserClassement = $options['classement'] === true && count($losers) > 0;
        $modeTheme = $partie->getTournoi()?->getMode() === ModeTournoi::THEME_VS_THEME;

        $winnersParId = [];
        foreach ($winners as $winner) {
            $winnersParId[(int) $winner->getId()] = $winner;
        }

        $losersParId = [];
        foreach ($losers as $loser) {
            $losersParId[(int) $loser->getId()] = $loser;
        }

        $swapsPayload = $this->decoderJsonArray((string) $request->request->get('repechage_json', '[]'));
        $classementPayload = $this->decoderJsonArray((string) $request->request->get('classement_json', '[]'));
        $impairPayload = $this->decoderJsonArray((string) $request->request->get('impair_json', '{}'));

        $decisionImpair = $manche->getDecisionImpair();
        if ($decisionImpair instanceof DecisionImpair && $decisionImpair->getAction() === ActionImpair::EN_ATTENTE) {
            $positionImpair = is_string($impairPayload['position'] ?? null)
                ? (string) $impairPayload['position']
                : 'middle';

            if ($positionImpair === 'losers') {
                $decisionImpair
                    ->setAction(ActionImpair::ELIMINER)
                    ->setElementRemplacant(null)
                    ->setResolvedAt(new \DateTimeImmutable());
            } elseif ($positionImpair === 'winner') {
                $winnerId = (int) ($impairPayload['winnerId'] ?? 0);
                if ($winnerId < 1 || !isset($winnersParId[$winnerId])) {
                    $this->addFlash('danger', 'Slot gagnant invalide pour l\'élément impair.');

                    return $this->redirectToRoute('app_partie_play', ['id' => $partie->getId()]);
                }

                $winnerRemplace = $winnersParId[$winnerId];
                if ($modeTheme
                    && $decisionImpair->getElementImpair() instanceof Element
                    && $decisionImpair->getElementImpair()?->getTheme()?->getId() !== $winnerRemplace->getTheme()?->getId()
                ) {
                    $this->addFlash('danger', 'Mode VS thème : l\'élément impair doit remplacer un gagnant du même thème.');

                    return $this->redirectToRoute('app_partie_play', ['id' => $partie->getId()]);
                }

                $decisionImpair
                    ->setAction(ActionImpair::REMPLACER)
                    ->setElementRemplacant($winnerRemplace)
                    ->setResolvedAt(new \DateTimeImmutable());
            } else {
                $this->addFlash('danger', 'Place d\'abord l\'élément impair : perdants ou remplacement d\'un gagnant.');

                return $this->redirectToRoute('app_partie_play', ['id' => $partie->getId()]);
            }
        }

        $swaps = [];
        $promotedLosers = [];
        $demotedWinners = [];
        if ($autoriserRepechage) {
            foreach ($swapsPayload as $rawSwap) {
                if (!is_array($rawSwap)) {
                    continue;
                }

                $loserId = (int) ($rawSwap['loserId'] ?? 0);
                $winnerId = (int) ($rawSwap['winnerId'] ?? 0);
                if ($loserId < 1 || $winnerId < 1) {
                    continue;
                }

                if (!isset($losersParId[$loserId]) || !isset($winnersParId[$winnerId])) {
                    continue;
                }

                if (isset($promotedLosers[$loserId]) || isset($demotedWinners[$winnerId])) {
                    continue;
                }

                $loser = $losersParId[$loserId];
                $winner = $winnersParId[$winnerId];

                if ($modeTheme && $loser->getTheme()?->getId() !== $winner->getTheme()?->getId()) {
                    continue;
                }

                $swaps[] = [
                    'loser' => $loser,
                    'winner' => $winner,
                ];
                $promotedLosers[$loserId] = true;
                $demotedWinners[$winnerId] = true;
            }
        }

        foreach ($manche->getRepechages()->toArray() as $repechage) {
            $manche->removeRepechage($repechage);
            $entityManager->remove($repechage);
        }
        foreach ($manche->getClassementsPerdants()->toArray() as $classement) {
            $manche->removeClassementPerdant($classement);
            $entityManager->remove($classement);
        }

        $ordreRepechage = 1;
        foreach ($swaps as $swap) {
            $repechage = new Repechage();
            $repechage
                ->setManche($manche)
                ->setTheme($swap['winner']->getTheme())
                ->setOrdre($ordreRepechage++)
                ->setPerdant($swap['loser'])
                ->setVainqueurCible($swap['winner'])
                ->setVainqueurFinal($swap['loser'])
                ->setEtat(EtatDuel::TERMINE);

            $manche->addRepechage($repechage);
        }

        $finalLosers = $losersParId;
        foreach (array_keys($promotedLosers) as $promotedLoserId) {
            unset($finalLosers[(int) $promotedLoserId]);
        }
        foreach (array_keys($demotedWinners) as $demotedWinnerId) {
            $demotedWinnerId = (int) $demotedWinnerId;
            if (isset($winnersParId[$demotedWinnerId])) {
                $finalLosers[$demotedWinnerId] = $winnersParId[$demotedWinnerId];
            }
        }

        if ($decisionImpair instanceof DecisionImpair) {
            if ($decisionImpair->getAction() === ActionImpair::ELIMINER
                && $decisionImpair->getElementImpair() instanceof Element
            ) {
                $finalLosers[(int) $decisionImpair->getElementImpair()->getId()] = $decisionImpair->getElementImpair();
            }

            if ($decisionImpair->getAction() === ActionImpair::REMPLACER
                && $decisionImpair->getElementRemplacant() instanceof Element
            ) {
                $finalLosers[(int) $decisionImpair->getElementRemplacant()->getId()] = $decisionImpair->getElementRemplacant();
            }
        }

        if ($autoriserClassement) {
            $ordreIds = [];
            foreach ($classementPayload as $rawId) {
                $id = is_array($rawId) ? (int) ($rawId['id'] ?? 0) : (int) $rawId;
                if ($id > 0 && isset($finalLosers[$id]) && !isset($ordreIds[$id])) {
                    $ordreIds[$id] = true;
                }
            }

            $rang = 1;
            foreach (array_keys($ordreIds) as $id) {
                $element = $finalLosers[$id];
                $classement = new ClassementPerdant();
                $classement
                    ->setManche($manche)
                    ->setElement($element)
                    ->setTheme($element->getTheme())
                    ->setRang($rang++);
                $manche->addClassementPerdant($classement);
            }

            $restants = [];
            foreach ($finalLosers as $id => $element) {
                if (!isset($ordreIds[(int) $id])) {
                    $restants[] = $element;
                }
            }

            usort($restants, static fn (Element $a, Element $b): int => strcmp($a->getTitre(), $b->getTitre()));

            foreach ($restants as $element) {
                $classement = new ClassementPerdant();
                $classement
                    ->setManche($manche)
                    ->setElement($element)
                    ->setTheme($element->getTheme())
                    ->setRang($rang++);
                $manche->addClassementPerdant($classement);
            }
        }

        $qualifies = $this->collecterQualifiesManche($manche);
        $this->finaliserMancheEtPasserSuivante($partie, $manche, $qualifies, $entityManager, $options);
        $entityManager->flush();

        return $this->redirectToRoute('app_partie_play', ['id' => $partie->getId()]);
    }

    /**
     * Route HTTP: point d'entrée utilisateur.
     */
    #[Route('/parties/{id}/impair/decision', name: 'app_partie_decision_impair', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function decisionImpair(Partie $partie, Request $request, EntityManagerInterface $entityManager): Response
    {
        $joueur = $this->getUser();
        if (!($joueur instanceof Joueur) || $partie->getJoueur()?->getId() !== $joueur->getId()) {
            throw $this->createAccessDeniedException('Cette partie ne t\'appartient pas.');
        }

        $manche = $this->trouverMancheCourante($partie);
        if (!($manche instanceof Manche) || !($manche->getDecisionImpair() instanceof DecisionImpair)) {
            $this->addFlash('danger', 'Aucune décision d\'impair en attente.');

            return $this->redirectToRoute('app_partie_play', ['id' => $partie->getId()]);
        }

        $decision = $manche->getDecisionImpair();
        if ($decision->getAction() !== ActionImpair::EN_ATTENTE) {
            return $this->redirectToRoute('app_partie_play', ['id' => $partie->getId()]);
        }

        $action = (string) $request->request->get('action', 'eliminer');
        if ($action === 'remplacer') {
            $idRemplacant = (int) $request->request->get('element_remplacant', 0);
            $candidats = $this->collecterQualifiesManche($manche);
            $elementRemplacant = null;

            foreach ($candidats as $candidat) {
                if ($candidat->getId() === $idRemplacant) {
                    $elementRemplacant = $candidat;
                    break;
                }
            }

            if (!($elementRemplacant instanceof Element)) {
                $this->addFlash('danger', 'Élément remplaçant invalide.');

                return $this->redirectToRoute('app_partie_play', ['id' => $partie->getId()]);
            }

            $decision
                ->setAction(ActionImpair::REMPLACER)
                ->setElementRemplacant($elementRemplacant)
                ->setResolvedAt(new \DateTimeImmutable());
        } else {
            $decision
                ->setAction(ActionImpair::ELIMINER)
                ->setElementRemplacant(null)
                ->setResolvedAt(new \DateTimeImmutable());
        }

        $manche->setEtat(EtatManche::EN_COURS);
        $partie->setEtat(EtatPartie::EN_COURS)->touch();
        $entityManager->flush();

        return $this->redirectToRoute('app_partie_play', ['id' => $partie->getId()]);
    }

    /**
     * Route HTTP: point d'entrée utilisateur.
     */
    #[Route('/parties/{id}/supprimer', name: 'app_partie_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function supprimer(Partie $partie, Request $request, EntityManagerInterface $entityManager): Response
    {
        $joueur = $this->getUser();
        if (!($joueur instanceof Joueur) || $partie->getJoueur()?->getId() !== $joueur->getId()) {
            throw $this->createAccessDeniedException('Cette partie ne t\'appartient pas.');
        }

        $id = (int) $partie->getId();
        $entityManager->remove($partie);
        $entityManager->flush();
        $this->effacerOptionsPartie($request, $id);

        $this->addFlash('success', 'Partie en cours supprimée.');

        if ($request->isXmlHttpRequest()) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        return $this->redirectToRoute('app_compte');
    }

    /**
     * Route HTTP: point d'entrée utilisateur.
     */
    #[Route('/tournois/{id}/stats', name: 'app_tournoi_stats', methods: ['GET'])]
    public function stats(Tournoi $tournoi, PartieRepository $partieRepository, Request $request): Response
    {
        if ($tournoi->isBrouillon()) {
            throw $this->createNotFoundException('Ce tournoi n\'est pas public.');
        }

        $joueur = $this->getUser();
        $partieActive = null;
        if ($joueur instanceof Joueur) {
            $partieActive = $partieRepository->findPartieActivePourJoueurEtTournoi($joueur, $tournoi);
        }

        $pool = $this->analyserPoolTournoi($tournoi);

        $partiesTerminees = $partieRepository->findPartiesTermineesParTournoi($tournoi);
        $totalParties = count($partiesTerminees);

        $statsParElement = [];
        foreach ($tournoi->getElementsActifs() as $element) {
            $statsParElement[(int) $element->getId()] = [
                'element' => $element,
                'duelsJoues' => $element->getDuelsJouesCumules(),
                'duelsGagnes' => $element->getDuelsGagnesCumules(),
                'tournoisGagnes' => $element->getTournoisGagnesCumules(),
            ];
        }

        $classement = array_values(array_map(static function (array $ligne) use ($totalParties): array {
            $duelsJoues = (int) $ligne['duelsJoues'];
            $ratioDuel = $duelsJoues > 0
                ? ((int) $ligne['duelsGagnes'] / $duelsJoues) * 100
                : 0.0;
            $ratioFinal = $totalParties > 0
                ? ((int) $ligne['tournoisGagnes'] / $totalParties) * 100
                : 0.0;

            $ligne['ratioDuel'] = $ratioDuel;
            $ligne['ratioFinal'] = $ratioFinal;

            return $ligne;
        }, $statsParElement));

        usort($classement, static function (array $a, array $b): int {
            if (abs($a['ratioFinal'] - $b['ratioFinal']) > 0.001) {
                return $b['ratioFinal'] <=> $a['ratioFinal'];
            }

            return $b['ratioDuel'] <=> $a['ratioDuel'];
        });

        $elementsParPage = 10;
        $totalElementsClassement = count($classement);
        $totalPages = $totalElementsClassement > 0
            ? (int) ceil($totalElementsClassement / $elementsParPage)
            : 1;

        $pageCourante = max(1, (int) $request->query->get('page', 1));
        if ($pageCourante > $totalPages) {
            $pageCourante = $totalPages;
        }

        $offsetClassement = ($pageCourante - 1) * $elementsParPage;
        $classement = array_slice($classement, $offsetClassement, $elementsParPage);

        $partiePopup = null;
        $classementPartiePopup = [];
        $afficherClassementPartiePopup = false;
        $idPartiePopup = (int) $request->query->get('partie', 0);
        if ($idPartiePopup > 0) {
            foreach ($partiesTerminees as $partieTerminee) {
                if ((int) $partieTerminee->getId() === $idPartiePopup) {
                    $partiePopup = $partieTerminee;
                    $classementPartiePopup = $this->construireClassementPartie($partieTerminee);
                    $afficherClassementPartiePopup = $this->optionClassementActivePourPartie($request, $partieTerminee);
                    if (!$afficherClassementPartiePopup) {
                        $classementPartiePopup = [];
                    }
                    break;
                }
            }
        }

        return $this->render('tournoi/stats.html.twig', [
            'tournoi' => $tournoi,
            'classement' => $classement,
            'offsetClassement' => $offsetClassement,
            'pageCourante' => $pageCourante,
            'totalPages' => $totalPages,
            'totalParties' => $totalParties,
            'partieActive' => $partieActive,
            'partiePopup' => $partiePopup,
            'classementPartiePopup' => $classementPartiePopup,
            'afficherClassementPartiePopup' => $afficherClassementPartiePopup,
            'compteThemeA' => $pool['compteThemeA'],
            'compteThemeB' => $pool['compteThemeB'],
        ]);
    }

    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function avancerProgressionPartie(Partie $partie, EntityManagerInterface $entityManager, array $options): void
    {
        $securiteBoucle = 0;

        while ($securiteBoucle < 20) {
            $securiteBoucle++;

            $manche = $this->trouverMancheCourante($partie);
            if (!($manche instanceof Manche)) {
                break;
            }

            if ($this->trouverDuelActif($manche) instanceof Duel || $this->trouverRepechageActif($manche) instanceof Repechage) {
                $partie->setEtat(EtatPartie::EN_COURS)->touch();
                $manche->setEtat(EtatManche::EN_COURS);
                $entityManager->flush();

                break;
            }

            if ($manche->getEtat() === EtatManche::EN_PREPARATION) {
                $partie->setEtat(EtatPartie::EN_COURS)->touch();
                $entityManager->flush();

                break;
            }

            $intermanche = $this->construireIntermancheContext($manche);
            $qualifies = $intermanche['winners'];
            $perdants = $intermanche['losers'];
            $decision = $manche->getDecisionImpair();

            if (count($qualifies) < 1) {
                $this->finaliserMancheEtPasserSuivante($partie, $manche, $qualifies, $entityManager, $options);
                $entityManager->flush();

                break;
            }

            $doitAfficherIntermanche = $this->doitAfficherIntermanche($options, $qualifies, $perdants, $decision);
            if ($doitAfficherIntermanche) {
                $manche->setEtat(EtatManche::EN_PREPARATION);
                $partie->setEtat(EtatPartie::EN_COURS)->touch();
                $entityManager->flush();

                break;
            }

            $this->finaliserMancheEtPasserSuivante($partie, $manche, $qualifies, $entityManager, $options);
            $entityManager->flush();
        }
    }

    /**
     * @return Element[]
     */
    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function collecterQualifiesManche(Manche $manche): array
    {
        $qualifies = [];

        foreach ($manche->getDuels() as $duel) {
            if ($duel->getEtat() === EtatDuel::TERMINE && $duel->getVainqueur() instanceof Element) {
                $qualifies[] = $duel->getVainqueur();
            }
        }

        foreach ($manche->getRepechages() as $repechage) {
            if ($repechage->getEtat() !== EtatDuel::TERMINE || !($repechage->getVainqueurFinal() instanceof Element)) {
                continue;
            }

            $idCible = $repechage->getVainqueurCible()?->getId();
            if ($idCible !== null) {
                $qualifies = array_values(array_filter($qualifies, static fn (Element $item): bool => $item->getId() !== $idCible));
            }

            $qualifies[] = $repechage->getVainqueurFinal();
        }

        $decision = $manche->getDecisionImpair();
        if ($decision instanceof DecisionImpair && $decision->getAction() === ActionImpair::REMPLACER) {
            $idRemplacant = $decision->getElementRemplacant()?->getId();
            if ($idRemplacant !== null) {
                $qualifies = array_values(array_filter($qualifies, static fn (Element $item): bool => $item->getId() !== $idRemplacant));
            }

            if ($decision->getElementImpair() instanceof Element) {
                $qualifies[] = $decision->getElementImpair();
            }
        }

        return $this->uniquerElements($qualifies);
    }

    /**
     * @return array{winners: Element[], losers: Element[], oddElement: ?Element}
     */
    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function construireIntermancheContext(Manche $manche): array
    {
        $winners = [];
        $losers = [];
        $oddElement = null;

        $duels = $manche->getDuels()->toArray();
        usort($duels, static fn (Duel $a, Duel $b): int => $a->getOrdre() <=> $b->getOrdre());

        foreach ($duels as $duel) {
            if ($duel->getEtat() !== EtatDuel::TERMINE || !($duel->getVainqueur() instanceof Element)) {
                continue;
            }

            $winners[] = $duel->getVainqueur();

            $loser = $duel->getElementA()?->getId() === $duel->getVainqueur()?->getId()
                ? $duel->getElementB()
                : $duel->getElementA();
            if ($loser instanceof Element) {
                $losers[] = $loser;
            }
        }

        $decision = $manche->getDecisionImpair();
        if ($decision instanceof DecisionImpair) {
            if ($decision->getAction() === ActionImpair::EN_ATTENTE && $decision->getElementImpair() instanceof Element) {
                $oddElement = $decision->getElementImpair();
            }

            if ($decision->getAction() === ActionImpair::ELIMINER && $decision->getElementImpair() instanceof Element) {
                $losers[] = $decision->getElementImpair();
            }

            if ($decision->getAction() === ActionImpair::REMPLACER) {
                if ($decision->getElementRemplacant() instanceof Element) {
                    $idRemplacant = $decision->getElementRemplacant()->getId();
                    $winners = array_values(array_filter($winners, static fn (Element $item): bool => $item->getId() !== $idRemplacant));
                    $losers[] = $decision->getElementRemplacant();
                }

                if ($decision->getElementImpair() instanceof Element) {
                    $winners[] = $decision->getElementImpair();
                }
            }
        }

        return [
            'winners' => $this->uniquerElements($winners),
            'losers' => $this->uniquerElements($losers),
            'oddElement' => $oddElement,
        ];
    }

    /**
     * @param array{repechage: bool, classement: bool, utiliserTous: bool} $options
     * @param Element[] $qualifies
     * @param Element[] $perdants
     */
    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function doitAfficherIntermanche(
        array $options,
        array $qualifies,
        array $perdants,
        ?DecisionImpair $decisionImpair = null
    ): bool {
        if ($decisionImpair instanceof DecisionImpair
            && $decisionImpair->getAction() === ActionImpair::EN_ATTENTE
            && $decisionImpair->getElementImpair() instanceof Element
        ) {
            return true;
        }

        if (count($qualifies) <= 1 || count($perdants) < 1) {
            return false;
        }

        return $options['repechage'] === true || $options['classement'] === true;
    }

    /**
     * @param Element[] $qualifies
     */
    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function finaliserMancheEtPasserSuivante(
        Partie $partie,
        Manche $manche,
        array $qualifies,
        EntityManagerInterface $entityManager,
        array $options
    ): void {
        $manche->setEtat(EtatManche::TERMINEE)->setClosedAt(new \DateTimeImmutable());

        if (count($qualifies) < 1) {
            $partie->setVainqueurFinal(null);
            $this->consoliderStatsPartie($partie, ($options['classement'] ?? false) === true);
            $entityManager->flush();

            $partie
                ->setEtat(EtatPartie::TERMINEE)
                ->setFinishedAt(new \DateTimeImmutable())
                ->touch();

            return;
        }

        if (count($qualifies) === 1) {
            $partie->setVainqueurFinal($qualifies[0]);
            $this->consoliderStatsPartie($partie, ($options['classement'] ?? false) === true);
            $entityManager->flush();

            $partie
                ->setEtat(EtatPartie::TERMINEE)
                ->setFinishedAt(new \DateTimeImmutable())
                ->touch();

            return;
        }

        $numeroSuivant = $manche->getNumero() + 1;
        $this->creerMancheDepuisElements($partie, $qualifies, $numeroSuivant, $partie->getTournoi(), $entityManager);
        $partie
            ->setVainqueurFinal(null)
            ->setEtat(EtatPartie::EN_COURS)
            ->setFinishedAt(null)
            ->touch();
    }

    /**
     * @return array<int, mixed>
     */
    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function decoderJsonArray(string $payload): array
    {
        if (trim($payload) === '') {
            return [];
        }

        try {
            $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function creerClassementAutoPerdants(Manche $manche): void
    {
        $perdants = [];
        foreach ($manche->getDuels() as $duel) {
            if ($duel->getEtat() !== EtatDuel::TERMINE || !($duel->getVainqueur() instanceof Element)) {
                continue;
            }

            $perdant = null;
            if ($duel->getElementA()?->getId() === $duel->getVainqueur()?->getId()) {
                $perdant = $duel->getElementB();
            } else {
                $perdant = $duel->getElementA();
            }

            if ($perdant instanceof Element) {
                $perdants[] = $perdant;
            }
        }

        usort($perdants, static fn (Element $a, Element $b): int => strcmp($a->getTitre(), $b->getTitre()));
        $perdants = $this->uniquerElements($perdants);

        $rang = 1;
        foreach ($perdants as $perdant) {
            $classement = new ClassementPerdant();
            $classement
                ->setManche($manche)
                ->setElement($perdant)
                ->setTheme($perdant->getTheme())
                ->setRang($rang++);

            $manche->addClassementPerdant($classement);
        }
    }

    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function creerRepechagesSiNecessaire(Manche $manche, ?Tournoi $tournoi): int
    {
        if (!($tournoi instanceof Tournoi)) {
            return 0;
        }

        $duelsTermines = array_values(array_filter($manche->getDuels()->toArray(), static fn (Duel $duel): bool => $duel->getEtat() === EtatDuel::TERMINE));
        if (count($duelsTermines) < 2) {
            return 0;
        }

        $winners = [];
        $losers = [];
        foreach ($duelsTermines as $duel) {
            if (!($duel->getVainqueur() instanceof Element)) {
                continue;
            }

            $winners[] = $duel->getVainqueur();
            if ($duel->getElementA()?->getId() === $duel->getVainqueur()?->getId() && $duel->getElementB() instanceof Element) {
                $losers[] = $duel->getElementB();
            } elseif ($duel->getElementA() instanceof Element) {
                $losers[] = $duel->getElementA();
            }
        }

        if ($winners === [] || $losers === []) {
            return 0;
        }

        $nombre = 0;

        if ($tournoi->getMode() === ModeTournoi::THEME_VS_THEME) {
            $winnersParTheme = [];
            foreach ($winners as $winner) {
                $winnersParTheme[$winner->getTheme()?->getId() ?? 0][] = $winner;
            }

            $losersParTheme = [];
            foreach ($losers as $loser) {
                $losersParTheme[$loser->getTheme()?->getId() ?? 0][] = $loser;
            }

            $ordre = 1;
            foreach ($losersParTheme as $themeId => $listeLosers) {
                if (!isset($winnersParTheme[$themeId]) || $winnersParTheme[$themeId] === []) {
                    continue;
                }

                $perdant = array_shift($listeLosers);
                $vainqueur = array_shift($winnersParTheme[$themeId]);
                if (!($perdant instanceof Element) || !($vainqueur instanceof Element)) {
                    continue;
                }

                $repechage = new Repechage();
                $repechage
                    ->setManche($manche)
                    ->setTheme($perdant->getTheme())
                    ->setOrdre($ordre++)
                    ->setPerdant($perdant)
                    ->setVainqueurCible($vainqueur)
                    ->setEtat(EtatDuel::A_JOUER);

                $manche->addRepechage($repechage);
                $nombre++;
            }

            return $nombre;
        }

        $perdant = $losers[0] ?? null;
        $vainqueur = $winners[0] ?? null;
        if ($perdant instanceof Element && $vainqueur instanceof Element) {
            $repechage = new Repechage();
            $repechage
                ->setManche($manche)
                ->setTheme(null)
                ->setOrdre(1)
                ->setPerdant($perdant)
                ->setVainqueurCible($vainqueur)
                ->setEtat(EtatDuel::A_JOUER);

            $manche->addRepechage($repechage);

            return 1;
        }

        return 0;
    }

    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function trouverMancheCourante(Partie $partie): ?Manche
    {
        $manches = $partie->getManches()->toArray();
        usort($manches, static fn (Manche $a, Manche $b): int => $a->getNumero() <=> $b->getNumero());

        $courante = null;
        foreach ($manches as $manche) {
            if ($manche->getEtat() !== EtatManche::TERMINEE) {
                $courante = $manche;
            }
        }

        if ($courante instanceof Manche) {
            return $courante;
        }

        return $manches === [] ? null : end($manches);
    }

    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function trouverDuelActif(Manche $manche): ?Duel
    {
        $duels = $manche->getDuels()->toArray();
        usort($duels, static fn (Duel $a, Duel $b): int => $a->getOrdre() <=> $b->getOrdre());

        foreach ($duels as $duel) {
            if ($duel->getEtat() === EtatDuel::A_JOUER) {
                return $duel;
            }
        }

        return null;
    }

    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function trouverRepechageActif(Manche $manche): ?Repechage
    {
        $repechages = $manche->getRepechages()->toArray();
        usort($repechages, static fn (Repechage $a, Repechage $b): int => $a->getOrdre() <=> $b->getOrdre());

        foreach ($repechages as $repechage) {
            if ($repechage->getEtat() === EtatDuel::A_JOUER) {
                return $repechage;
            }
        }

        return null;
    }

    /**
     * @param Element[] $elements
     */
    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function creerMancheDepuisElements(
        Partie $partie,
        array $elements,
        int $numero,
        ?Tournoi $tournoi,
        EntityManagerInterface $entityManager
    ): void {
        if (!($tournoi instanceof Tournoi)) {
            return;
        }

        $manche = new Manche();
        $manche
            ->setPartie($partie)
            ->setNumero($numero)
            ->setType($this->typeMancheDepuisCompteur(count($elements)))
            ->setStrategieAppariement(
                $tournoi->getMode() === ModeTournoi::THEME_VS_THEME
                    ? ModeAppariement::INTER_THEME_MAX
                    : ModeAppariement::LIBRE
            )
            ->setEtat(EtatManche::EN_COURS)
            ->setClosedAt(null);

        $partie->addManche($manche);
        $entityManager->persist($manche);

        [$duels, $elementImpair] = $this->construireDuelsPourElements($elements, $tournoi, $manche);
        foreach ($duels as $duel) {
            $manche->addDuel($duel);
            $entityManager->persist($duel);
        }

        if ($elementImpair instanceof Element) {
            $decision = new DecisionImpair();
            $decision
                ->setManche($manche)
                ->setElementImpair($elementImpair)
                ->setAction(ActionImpair::EN_ATTENTE)
                ->setElementRemplacant(null)
                ->setResolvedAt(null);

            $manche->setDecisionImpair($decision);
            $entityManager->persist($decision);
        }
    }

    /**
     * @param Element[] $elements
     * @return array{0: Duel[], 1: ?Element}
     */
    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function construireDuelsPourElements(array $elements, Tournoi $tournoi, Manche $manche): array
    {
        $elements = $this->uniquerElements($elements);
        shuffle($elements);

        $ordre = 1;
        $duels = [];
        $impair = null;

        if ($tournoi->getMode() === ModeTournoi::THEME_VS_THEME) {
            $aId = $tournoi->getThemeA()?->getId();
            $bId = $tournoi->getThemeB()?->getId();

            $groupeA = [];
            $groupeB = [];
            $reste = [];

            foreach ($elements as $element) {
                $themeId = $element->getTheme()?->getId();
                if ($aId !== null && $themeId === $aId) {
                    $groupeA[] = $element;
                    continue;
                }
                if ($bId !== null && $themeId === $bId) {
                    $groupeB[] = $element;
                    continue;
                }
                $reste[] = $element;
            }

            shuffle($groupeA);
            shuffle($groupeB);
            shuffle($reste);

            while ($groupeA !== [] && $groupeB !== []) {
                $elementA = array_shift($groupeA);
                $elementB = array_shift($groupeB);
                if (!($elementA instanceof Element) || !($elementB instanceof Element)) {
                    break;
                }

                $duel = (new Duel())
                    ->setManche($manche)
                    ->setOrdre($ordre++)
                    ->setElementA($elementA)
                    ->setElementB($elementB)
                    ->setEtat(EtatDuel::A_JOUER)
                    ->setTypeConfrontation(TypeConfrontation::INTER_THEME);
                $duels[] = $duel;
            }

            $queue = array_merge($groupeA, $groupeB, $reste);
            while (count($queue) >= 2) {
                $elementA = array_shift($queue);
                $elementB = array_shift($queue);
                if (!($elementA instanceof Element) || !($elementB instanceof Element)) {
                    continue;
                }

                $type = ($elementA->getTheme()?->getId() === $elementB->getTheme()?->getId())
                    ? TypeConfrontation::INTRA_THEME
                    : TypeConfrontation::LIBRE;

                $duel = (new Duel())
                    ->setManche($manche)
                    ->setOrdre($ordre++)
                    ->setElementA($elementA)
                    ->setElementB($elementB)
                    ->setEtat(EtatDuel::A_JOUER)
                    ->setTypeConfrontation($type);
                $duels[] = $duel;
            }

            if (count($queue) === 1 && $queue[0] instanceof Element) {
                $impair = $queue[0];
            }

            return [$duels, $impair];
        }

        $queue = $elements;
        while (count($queue) >= 2) {
            $elementA = array_shift($queue);
            $elementB = array_shift($queue);
            if (!($elementA instanceof Element) || !($elementB instanceof Element)) {
                continue;
            }

            $duel = (new Duel())
                ->setManche($manche)
                ->setOrdre($ordre++)
                ->setElementA($elementA)
                ->setElementB($elementB)
                ->setEtat(EtatDuel::A_JOUER)
                ->setTypeConfrontation(TypeConfrontation::LIBRE);
            $duels[] = $duel;
        }

        if (count($queue) === 1 && $queue[0] instanceof Element) {
            $impair = $queue[0];
        }

        return [$duels, $impair];
    }

    /**
     * @return array{elements: Element[], compteThemeA: int, compteThemeB: int, elementsThemeA: Element[], elementsThemeB: Element[]}
     */
    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function analyserPoolTournoi(Tournoi $tournoi): array
    {
        $elements = array_values($tournoi->getElementsActifs());

        $themeAId = $tournoi->getThemeA()?->getId();
        $themeBId = $tournoi->getThemeB()?->getId();

        $elementsThemeA = [];
        $elementsThemeB = [];

        foreach ($elements as $element) {
            $themeId = $element->getTheme()?->getId();
            if ($themeAId !== null && $themeId === $themeAId) {
                $elementsThemeA[] = $element;
            } elseif ($themeBId !== null && $themeId === $themeBId) {
                $elementsThemeB[] = $element;
            }
        }

        return [
            'elements' => $elements,
            'compteThemeA' => count($elementsThemeA),
            'compteThemeB' => count($elementsThemeB),
            'elementsThemeA' => $elementsThemeA,
            'elementsThemeB' => $elementsThemeB,
        ];
    }

    /**
     * @param array{elements: Element[], compteThemeA: int, compteThemeB: int, elementsThemeA: Element[], elementsThemeB: Element[]} $pool
     * @return int[]
     */
    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function calculerTaillesDisponibles(Tournoi $tournoi, array $pool): array
    {
        $reference = count($pool['elements']);
        if ($tournoi->getMode() === ModeTournoi::THEME_VS_THEME) {
            $reference = min($pool['compteThemeA'], $pool['compteThemeB']) * 2;
        }

        $max = $this->plusGrandePuissanceDeDeuxInferieureOuEgale($reference);
        $tailles = [];
        while ($max >= self::MIN_TAILLE) {
            $tailles[] = $max;
            $max = intdiv($max, 2);
        }

        return $tailles;
    }

    /**
     * @param array{elements: Element[], compteThemeA: int, compteThemeB: int, elementsThemeA: Element[], elementsThemeB: Element[]} $pool
     * @return Element[]
     */
    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function construireParticipantsInitial(Tournoi $tournoi, array $pool, bool $utiliserTous, int $tailleDemandee): array
    {
        $elements = $pool['elements'];
        if ($elements === []) {
            return [];
        }

        if ($tournoi->getMode() === ModeTournoi::THEME_VS_THEME) {
            $a = $pool['elementsThemeA'];
            $b = $pool['elementsThemeB'];
            shuffle($a);
            shuffle($b);

            if ($utiliserTous) {
                $mix = array_merge($a, $b);
                shuffle($mix);

                return $mix;
            }

            $perTheme = max(1, intdiv($tailleDemandee, 2));
            if (count($a) < $perTheme || count($b) < $perTheme) {
                throw new \InvalidArgumentException('Pas assez d\'éléments dans chaque thème pour cette taille.');
            }

            $selection = array_merge(array_slice($a, 0, $perTheme), array_slice($b, 0, $perTheme));
            shuffle($selection);

            return $selection;
        }

        shuffle($elements);

        if ($utiliserTous) {
            return $elements;
        }

        return array_slice($elements, 0, max(2, $tailleDemandee));
    }

    /**
     * @param Element[] $elements
     * @return Element[]
     */
    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function uniquerElements(array $elements): array
    {
        $map = [];
        foreach ($elements as $element) {
            $id = (int) $element->getId();
            if (!isset($map[$id])) {
                $map[$id] = $element;
            }
        }

        return array_values($map);
    }

    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function typeMancheDepuisCompteur(int $compteur): TypeManche
    {
        $base = $this->plusGrandePuissanceDeDeuxInferieureOuEgale($compteur);
        if ($base >= 64) {
            return TypeManche::M64;
        }
        if ($base >= 32) {
            return TypeManche::M32;
        }
        if ($base >= 16) {
            return TypeManche::M16;
        }
        if ($base >= 8) {
            return TypeManche::M8;
        }
        if ($base >= 4) {
            return TypeManche::M4;
        }

        return TypeManche::M2;
    }

    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function nomTypeManche(TypeManche $type): string
    {
        return match ($type) {
            TypeManche::M64 => '64',
            TypeManche::M32 => '32',
            TypeManche::M16 => '16',
            TypeManche::M8 => '8',
            TypeManche::M4 => '4',
            TypeManche::M2 => '2',
        };
    }

    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function compterElementsDansManche(Manche $manche): int
    {
        $ids = [];

        foreach ($manche->getDuels() as $duel) {
            if ($duel->getElementA() instanceof Element && $duel->getElementA()?->getId() !== null) {
                $ids[(int) $duel->getElementA()->getId()] = true;
            }

            if ($duel->getElementB() instanceof Element && $duel->getElementB()?->getId() !== null) {
                $ids[(int) $duel->getElementB()->getId()] = true;
            }
        }

        $decisionImpair = $manche->getDecisionImpair();
        if ($decisionImpair instanceof DecisionImpair
            && $decisionImpair->getElementImpair() instanceof Element
            && $decisionImpair->getElementImpair()?->getId() !== null
        ) {
            $ids[(int) $decisionImpair->getElementImpair()->getId()] = true;
        }

        return count($ids);
    }

    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function plusGrandePuissanceDeDeuxInferieureOuEgale(int $valeur): int
    {
        if ($valeur < 1) {
            return 0;
        }

        $resultat = 1;
        while (($resultat * 2) <= $valeur) {
            $resultat *= 2;
        }

        return $resultat;
    }

    /**
     * @param array{elements: Element[], compteThemeA: int, compteThemeB: int, elementsThemeA: Element[], elementsThemeB: Element[]} $pool
     */
    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function doitAfficherOptionToutUtiliser(Tournoi $tournoi, array $pool): bool
    {
        $total = count($pool['elements']);

        if ($tournoi->getMode() === ModeTournoi::THEME_VS_THEME) {
            return !$this->estTailleStandard($total)
                || $pool['compteThemeA'] !== $pool['compteThemeB'];
        }

        return !$this->estTailleStandard($total);
    }

    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function estTailleStandard(int $taille): bool
    {
        return in_array($taille, [8, 16, 32, 64], true);
    }

    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function consoliderStatsPartie(Partie $partie, bool $persisterClassement): void
    {
        if ($partie->isStatsConsolidees()) {
            return;
        }

        $statsParElement = [];

        foreach ($partie->getManches() as $manche) {
            foreach ($manche->getDuels() as $duel) {
                if ($duel->getEtat() !== EtatDuel::TERMINE) {
                    continue;
                }

                $this->enregistrerLigneStatElement($statsParElement, $duel->getElementA(), true, false);
                $this->enregistrerLigneStatElement($statsParElement, $duel->getElementB(), true, false);
                $this->enregistrerLigneStatElement($statsParElement, $duel->getVainqueur(), false, true);
            }

            foreach ($manche->getRepechages() as $repechage) {
                if ($repechage->getEtat() !== EtatDuel::TERMINE) {
                    continue;
                }

                $this->enregistrerLigneStatElement($statsParElement, $repechage->getPerdant(), true, false);
                $this->enregistrerLigneStatElement($statsParElement, $repechage->getVainqueurCible(), true, false);
                $this->enregistrerLigneStatElement($statsParElement, $repechage->getVainqueurFinal(), false, true);
            }
        }

        foreach ($statsParElement as $ligne) {
            $element = $ligne['element'];
            if (!($element instanceof Element)) {
                continue;
            }

            $element
                ->incrementerDuelsJoues((int) $ligne['duelsJoues'])
                ->incrementerDuelsGagnes((int) $ligne['duelsGagnes']);
        }

        if ($partie->getVainqueurFinal() instanceof Element) {
            $partie->getVainqueurFinal()->incrementerTournoisGagnes(1);
        }

        $classementIds = [];
        if ($persisterClassement) {
            $classementIds = array_map(
                static fn (Element $element): int => (int) $element->getId(),
                $this->construireClassementPartieDepuisManches($partie)
            );
        }

        $partie
            ->setClassementFinal($classementIds)
            ->setStatsConsolidees(true);
    }

    /**
     * @param array<int, array{element: Element, duelsJoues: int, duelsGagnes: int}> $statsParElement
     */
    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function enregistrerLigneStatElement(array &$statsParElement, ?Element $element, bool $duelJoue, bool $duelGagne): void
    {
        if (!($element instanceof Element) || !($element->getId() > 0)) {
            return;
        }

        $id = (int) $element->getId();
        if (!isset($statsParElement[$id])) {
            $statsParElement[$id] = [
                'element' => $element,
                'duelsJoues' => 0,
                'duelsGagnes' => 0,
            ];
        }

        if ($duelJoue) {
            $statsParElement[$id]['duelsJoues']++;
        }
        if ($duelGagne) {
            $statsParElement[$id]['duelsGagnes']++;
        }
    }

    /**
     * @return Element[]
     */
    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function construireClassementPartieDepuisManches(Partie $partie): array
    {
        $classement = [];
        $vus = [];

        $ajouter = static function (?Element $element) use (&$classement, &$vus): void {
            if (!($element instanceof Element) || !($element->getId() > 0)) {
                return;
            }

            $id = (int) $element->getId();
            if (isset($vus[$id])) {
                return;
            }

            $classement[] = $element;
            $vus[$id] = true;
        };

        $ajouter($partie->getVainqueurFinal());

        $manches = $partie->getManches()->toArray();
        usort($manches, static fn (Manche $a, Manche $b): int => $b->getNumero() <=> $a->getNumero());

        foreach ($manches as $manche) {
            $classementsPerdants = $manche->getClassementsPerdants()->toArray();
            if ($classementsPerdants !== []) {
                usort(
                    $classementsPerdants,
                    static fn (ClassementPerdant $a, ClassementPerdant $b): int => $b->getRang() <=> $a->getRang()
                );

                foreach ($classementsPerdants as $classementPerdant) {
                    $ajouter($classementPerdant->getElement());
                }

                continue;
            }

            $elimines = [];

            foreach ($manche->getDuels() as $duel) {
                if ($duel->getEtat() !== EtatDuel::TERMINE || !($duel->getVainqueur() instanceof Element)) {
                    continue;
                }

                $perdant = $duel->getElementA()?->getId() === $duel->getVainqueur()?->getId()
                    ? $duel->getElementB()
                    : $duel->getElementA();

                if ($perdant instanceof Element) {
                    $elimines[] = $perdant;
                }
            }

            foreach ($manche->getRepechages() as $repechage) {
                if ($repechage->getEtat() !== EtatDuel::TERMINE || !($repechage->getVainqueurFinal() instanceof Element)) {
                    continue;
                }

                $perdantRepechage = $repechage->getVainqueurFinal()?->getId() === $repechage->getPerdant()?->getId()
                    ? $repechage->getVainqueurCible()
                    : $repechage->getPerdant();

                if ($perdantRepechage instanceof Element) {
                    $elimines[] = $perdantRepechage;
                }
            }

            $decision = $manche->getDecisionImpair();
            if ($decision instanceof DecisionImpair) {
                if ($decision->getAction() === ActionImpair::ELIMINER && $decision->getElementImpair() instanceof Element) {
                    $elimines[] = $decision->getElementImpair();
                }

                if ($decision->getAction() === ActionImpair::REMPLACER && $decision->getElementRemplacant() instanceof Element) {
                    $elimines[] = $decision->getElementRemplacant();
                }
            }

            foreach ($this->uniquerElements($elimines) as $element) {
                $ajouter($element);
            }
        }

        $restants = array_values(array_filter(
            $this->collecterParticipantsPartie($partie),
            static fn (Element $element): bool => !isset($vus[(int) $element->getId()])
        ));
        usort($restants, static fn (Element $a, Element $b): int => strcmp($a->getTitre(), $b->getTitre()));

        foreach ($restants as $element) {
            $ajouter($element);
        }

        return $classement;
    }

    /**
     * @return Element[]
     */
    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function collecterParticipantsPartie(Partie $partie): array
    {
        $participants = [];

        foreach ($partie->getManches() as $manche) {
            foreach ($manche->getDuels() as $duel) {
                if ($duel->getElementA() instanceof Element) {
                    $participants[] = $duel->getElementA();
                }
                if ($duel->getElementB() instanceof Element) {
                    $participants[] = $duel->getElementB();
                }
            }

            foreach ($manche->getRepechages() as $repechage) {
                if ($repechage->getPerdant() instanceof Element) {
                    $participants[] = $repechage->getPerdant();
                }
                if ($repechage->getVainqueurCible() instanceof Element) {
                    $participants[] = $repechage->getVainqueurCible();
                }
            }

            $decision = $manche->getDecisionImpair();
            if ($decision instanceof DecisionImpair && $decision->getElementImpair() instanceof Element) {
                $participants[] = $decision->getElementImpair();
            }
        }

        return $this->uniquerElements($participants);
    }

    /**
     * @return Element[]
     */
    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function construireClassementPartie(Partie $partie): array
    {
        $elementsParId = [];
        foreach ($partie->getTournoi()?->getElements() ?? [] as $element) {
            $elementsParId[(int) $element->getId()] = $element;
        }

        $classementPersisted = [];
        foreach ($partie->getClassementFinal() as $elementId) {
            if (isset($elementsParId[$elementId])) {
                $classementPersisted[] = $elementsParId[$elementId];
            }
        }

        if ($classementPersisted !== []) {
            return $classementPersisted;
        }

        if ($partie->getManches()->count() > 0) {
            return $this->construireClassementPartieDepuisManches($partie);
        }

        if ($partie->getVainqueurFinal() instanceof Element) {
            return [$partie->getVainqueurFinal()];
        }

        return [];
    }

    /**
     * @return array{repechage: bool, classement: bool, utiliserTous: bool}
     */
    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function lireOptionsPartie(Request $request, Joueur $joueur, Partie $partie): array
    {
        $session = $request->getSession();
        $cle = $this->cleOptionsPartie((int) $partie->getId());

        $valeur = $session->get($cle);
        if (!is_array($valeur)) {
            return [
                'repechage' => $joueur->isActiverRepechage(),
                'classement' => $joueur->isActiverClassementPerdants(),
                'utiliserTous' => false,
            ];
        }

        return [
            'repechage' => (bool) ($valeur['repechage'] ?? $joueur->isActiverRepechage()),
            'classement' => (bool) ($valeur['classement'] ?? $joueur->isActiverClassementPerdants()),
            'utiliserTous' => (bool) ($valeur['utiliserTous'] ?? false),
        ];
    }

    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function optionClassementActivePourPartie(Request $request, Partie $partie): bool
    {
        if ($request->hasSession()) {
            $session = $request->getSession();
            $valeur = $session->get($this->cleOptionsPartie((int) $partie->getId()));
            if (is_array($valeur) && array_key_exists('classement', $valeur)) {
                return (bool) $valeur['classement'];
            }
        }

        return $partie->getClassementFinal() !== [];
    }

    /**
     * @param array{repechage: bool, classement: bool, utiliserTous: bool} $options
     */
    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function enregistrerOptionsPartie(Request $request, int $idPartie, array $options): void
    {
        $request->getSession()->set($this->cleOptionsPartie($idPartie), $options);
    }

    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function effacerOptionsPartie(Request $request, int $idPartie): void
    {
        $request->getSession()->remove($this->cleOptionsPartie($idPartie));
    }

    /**
     * Fonction utilitaire interne du contrôleur.
     */
    private function cleOptionsPartie(int $idPartie): string
    {
        return 'partie.options.'.$idPartie;
    }
}
