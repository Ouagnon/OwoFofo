<?php

namespace App\Controller;

use App\Entity\Joueur;
use App\Form\InscriptionType;
use App\Form\PasswordUpdateType;
use App\Form\PreferencesTournoiType;
use App\Form\PseudoUpdateType;
use App\Repository\JoueurRepository;
use App\Repository\PartieRepository;
use App\Repository\TournoiRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CompteController extends AbstractController
{
    /**
     * Route HTTP: point d'entrée utilisateur.
     */
    #[Route('/connexion', name: 'app_compte_connexion', methods: ['GET', 'POST'])]
    /**
     * Route HTTP: point d'entrée utilisateur.
     */
    #[Route('/compte/connexion', name: 'app_compte_connexion_alias', methods: ['GET', 'POST'])]
    public function connexion(AuthenticationUtils $utilitairesAuthentification): Response
    {
        if ($this->getUser() !== null) {
            return $this->redirectToRoute('app_home');
        }

        return $this->render('compte/connexion.html.twig', [
            'dernierIdentifiant' => $utilitairesAuthentification->getLastUsername(),
            'erreurConnexion' => $utilitairesAuthentification->getLastAuthenticationError(),
        ]);
    }

    /**
     * Route HTTP: point d'entrée utilisateur.
     */
    #[Route('/inscription', name: 'app_compte_inscription', methods: ['GET', 'POST'])]
    /**
     * Route HTTP: point d'entrée utilisateur.
     */
    #[Route('/compte/inscription', name: 'app_compte_inscription_alias', methods: ['GET', 'POST'])]
    public function inscription(
        Request $requete,
        EntityManagerInterface $gestionnaireEntites,
        UserPasswordHasherInterface $hacheurMotDePasse,
        JoueurRepository $depotJoueur
    ): Response {
        if ($this->getUser() !== null) {
            return $this->redirectToRoute('app_home');
        }

        $joueur = new Joueur();
        $formulaireInscription = $this->createForm(InscriptionType::class, $joueur);
        $formulaireInscription->handleRequest($requete);

        if ($formulaireInscription->isSubmitted() && $formulaireInscription->isValid()) {
            $nom = trim($joueur->getNom());
            if ($depotJoueur->findOneByNom($nom) !== null) {
                $formulaireInscription->get('nom')->addError(new FormError('Ce pseudo est déjà utilisé.'));
            } else {
                $motDePasseEnClair = (string) $formulaireInscription->get('motDePasseEnClair')->getData();
                $joueur->setMotDePasseHash($hacheurMotDePasse->hashPassword($joueur, $motDePasseEnClair));

                $gestionnaireEntites->persist($joueur);
                $gestionnaireEntites->flush();

                $this->addFlash('success', 'Compte créé. Tu peux maintenant te connecter.');

                return $this->redirectToRoute('app_compte_connexion');
            }
        }

        return $this->render('compte/inscription.html.twig', [
            'formulaireInscription' => $formulaireInscription,
        ]);
    }

    /**
     * Route HTTP: point d'entrée utilisateur.
     */
    #[Route('/deconnexion', name: 'app_compte_deconnexion', methods: ['GET'])]
    public function deconnexion(): never
    {
        throw new \LogicException('Cette méthode est interceptée par le firewall logout.');
    }

    /**
     * Route HTTP: point d'entrée utilisateur.
     */
    #[Route('/compte', name: 'app_compte', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function profil(
        Request $requete,
        UserPasswordHasherInterface $hacheurMotDePasse,
        EntityManagerInterface $gestionnaireEntites,
        JoueurRepository $depotJoueur,
        TournoiRepository $depotTournoi,
        PartieRepository $depotPartie
    ): Response {
        $joueur = $this->getUser();
        if (!$joueur instanceof Joueur) {
            throw $this->createAccessDeniedException('Session utilisateur invalide.');
        }

        $formulairePseudo = $this->createForm(PseudoUpdateType::class);
        $formulaireMotDePasse = $this->createForm(PasswordUpdateType::class);
        $formulairePreferences = $this->createForm(PreferencesTournoiType::class, $joueur);

        $formulairePseudo->handleRequest($requete);
        $formulaireMotDePasse->handleRequest($requete);
        $formulairePreferences->handleRequest($requete);

        if ($formulairePseudo->isSubmitted() && $formulairePseudo->isValid()) {
            $motDePasseActuel = (string) $formulairePseudo->get('motDePasseActuel')->getData();
            if (!$hacheurMotDePasse->isPasswordValid($joueur, $motDePasseActuel)) {
                $formulairePseudo->get('motDePasseActuel')->addError(new FormError('Le mot de passe saisi est incorrect.'));
            } else {
                $nouveauNom = trim((string) $formulairePseudo->get('nouveauNom')->getData());
                $joueurExistant = $depotJoueur->findOneByNom($nouveauNom);
                if ($joueurExistant !== null && $joueurExistant->getId() !== $joueur->getId()) {
                    $formulairePseudo->get('nouveauNom')->addError(new FormError('Ce pseudo est déjà utilisé.'));
                } else {
                    $joueur->setNom($nouveauNom);
                    $gestionnaireEntites->flush();
                    $this->addFlash('success', 'Pseudo mis à jour avec succès.');

                    return $this->redirectToRoute('app_compte');
                }
            }
        }

        if ($formulaireMotDePasse->isSubmitted() && $formulaireMotDePasse->isValid()) {
            $motDePasseActuel = (string) $formulaireMotDePasse->get('motDePasseActuel')->getData();
            if (!$hacheurMotDePasse->isPasswordValid($joueur, $motDePasseActuel)) {
                $formulaireMotDePasse->get('motDePasseActuel')->addError(new FormError('Le mot de passe saisi est incorrect.'));
            } else {
                $nouveauMotDePasse = (string) $formulaireMotDePasse->get('nouveauMotDePasse')->getData();
                $joueur->setMotDePasseHash($hacheurMotDePasse->hashPassword($joueur, $nouveauMotDePasse));
                $gestionnaireEntites->flush();

                $this->addFlash('success', 'Mot de passe mis à jour avec succès.');

                return $this->redirectToRoute('app_compte');
            }
        }

        if ($formulairePreferences->isSubmitted() && $formulairePreferences->isValid()) {
            $gestionnaireEntites->flush();
            $this->addFlash('success', 'Préférences de tournoi mises à jour.');

            return $this->redirectToRoute('app_compte');
        }

        return $this->render('compte/index.html.twig', [
            'joueur' => $joueur,
            'formulairePseudo' => $formulairePseudo,
            'formulaireMotDePasse' => $formulaireMotDePasse,
            'formulairePreferences' => $formulairePreferences,
            'tournoisBrouillon' => $depotTournoi->findBrouillonsParCreateur($joueur),
            'tournoisPublies' => $depotTournoi->findPubliesParCreateur($joueur),
            'partiesActives' => $depotPartie->findPartiesActivesParJoueur($joueur),
        ]);
    }
}
