/**
 * Gère toute la logique de lancement d'une partie depuis la page d'accueil.
 */
const initialiserModaleJouerAccueil = () => {
    // La modale principale de configuration de partie.
    const modale = document.getElementById('playTournamentModal');
    if (!modale) {
        return;
    }

    // La modale intermédiaire qui propose de reprendre une partie active.
    const modalePartieActive = document.getElementById('activePartieModal');

    // Empêche une double initialisation (Turbo + chargement direct).
    if (modale.dataset.playModalInit === '1') {
        return;
    }
    modale.dataset.playModalInit = '1';

    // Force les modales à être filles directes de body pour un z-index propre.
    if (modale.parentElement !== document.body) {
        document.body.appendChild(modale);
    }
    if (modalePartieActive && modalePartieActive.parentElement !== document.body) {
        document.body.appendChild(modalePartieActive);
    }

    // Tailles standard autorisées pour une partie.
    const taillesStandards = [64, 32, 16, 8];

    // Références de formulaire.
    const formulaire = document.getElementById('playTournamentForm');
    const selectChoix = document.getElementById('home-play-choice');
    const champCacheUtiliserTous = document.getElementById('home-play-utiliser-tous-hidden');
    const champCacheTaille = document.getElementById('home-play-taille-hidden');
    const boutonSoumettre = document.getElementById('playTournamentSubmitBtn');
    const boutonsJouer = document.querySelectorAll('.js-open-play-modal');

    // Références de la modale "partie active".
    const messagePartieActive = document.getElementById('activePartieModalMessage');
    const boutonReprendre = document.getElementById('activePartieResumeBtn');
    const boutonSupprimer = document.getElementById('activePartieDeleteBtn');

    // Si un élément clé manque, on stoppe proprement.
    if (!formulaire || !selectChoix || !champCacheUtiliserTous || !champCacheTaille || !boutonSoumettre) {
        return;
    }

    // Modèle d'URL de configuration, injecté depuis Twig.
    const modeleUrlConfig = modale.dataset.configTemplate || '';

    // Instances Bootstrap, si la bibliothèque est chargée.
    const instanceModaleConfig = window.bootstrap
        ? window.bootstrap.Modal.getOrCreateInstance(modale)
        : null;
    const instanceModalePartieActive = window.bootstrap && modalePartieActive
        ? window.bootstrap.Modal.getOrCreateInstance(modalePartieActive)
        : null;

    // État local pour la suppression d'une partie active.
    let boutonEnAttente = null;
    let urlSuppressionEnAttente = '';
    let suppressionEnCours = false;

    /** Indique si la taille est dans les tailles standards. */
    const estTailleStandard = (valeur) => taillesStandards.includes(valeur);

    /**
     * Construit la liste des tailles possibles selon le mode du tournoi.
     */
    const construireTailles = (mode, total, compteA, compteB) => {
        const borneMax = mode === 'theme_vs_theme'
            ? Math.min(compteA, compteB) * 2
            : total;

        return taillesStandards.filter((valeur) => valeur <= borneMax);
    };

    /** Détermine s'il faut afficher l'option "utiliser tous". */
    const doitAfficherOptionUtiliserTous = (total) => !estTailleStandard(total);

    /**
     * Remplit le select des choix de tailles et de l'option "tout utiliser".
     */
    const rendreChoix = (tailles, afficherToutUtiliser) => {
        selectChoix.innerHTML = '';

        const optionVide = document.createElement('option');
        optionVide.value = '';
        optionVide.textContent = '---';
        selectChoix.appendChild(optionVide);

        tailles.forEach((taille) => {
            const option = document.createElement('option');
            option.value = 'size:' + String(taille);
            option.textContent = String(taille) + ' éléments';
            selectChoix.appendChild(option);
        });

        if (afficherToutUtiliser) {
            const optionTout = document.createElement('option');
            optionTout.value = 'all';
            optionTout.textContent = 'Utiliser tous les éléments';
            selectChoix.appendChild(optionTout);
        }

        selectChoix.value = '';
    };

    /**
     * Synchronise les champs cachés pour envoyer les bonnes valeurs au backend.
     */
    const synchroniserChampsCaches = () => {
        champCacheUtiliserTous.value = '0';
        champCacheTaille.value = '0';

        const valeur = selectChoix.value;
        if (valeur.startsWith('size:')) {
            champCacheTaille.value = valeur.slice(5);
        } else if (valeur === 'all') {
            champCacheUtiliserTous.value = '1';
        }

        boutonSoumettre.disabled = (valeur === '');
    };

    selectChoix.addEventListener('change', synchroniserChampsCaches);

    /**
     * Hydrate la modale de configuration depuis les data-attributes du bouton cliqué.
     */
    const hydraterDepuisBouton = (bouton) => {
        if (!(bouton instanceof HTMLElement)) {
            return;
        }

        const idTournoi = bouton.dataset.tournoiId;
        const mode = bouton.dataset.mode || 'libre';
        const total = parseInt(bouton.dataset.total || '0', 10);
        const compteA = parseInt(bouton.dataset.countThemeA || '0', 10);
        const compteB = parseInt(bouton.dataset.countThemeB || '0', 10);
        const hintAfficherToutUtiliser = bouton.dataset.showUseAll === '1';

        const tailles = construireTailles(mode, total, compteA, compteB);
        let afficherToutUtiliser = hintAfficherToutUtiliser || doitAfficherOptionUtiliserTous(total);
        if (!afficherToutUtiliser && tailles.length === 0) {
            afficherToutUtiliser = true;
        }

        formulaire.action = modeleUrlConfig.replace('__ID__', idTournoi || '__ID__');

        rendreChoix(tailles, afficherToutUtiliser);
        synchroniserChampsCaches();
    };

    /** Ouvre la modale de configuration de partie. */
    const ouvrirConfiguration = (bouton) => {
        if (!(bouton instanceof HTMLElement)) {
            return;
        }

        hydraterDepuisBouton(bouton);
        if (instanceModaleConfig) {
            instanceModaleConfig.show();
            return;
        }

        // Fallback sans Bootstrap JS.
        if (formulaire.action) {
            window.location.href = formulaire.action;
        }
    };

    /** Nettoie les backdrops Bootstrap orphelins. */
    const nettoyerArrieresPlansModales = () => {
        if (document.querySelector('.modal.show')) {
            return;
        }

        document.querySelectorAll('.modal-backdrop').forEach((arrierePlan) => arrierePlan.remove());
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('padding-right');
    };

    /** Ferme la modale "partie active" puis ouvre la configuration. */
    const masquerPartieActivePuisOuvrirConfig = (bouton) => {
        if (!modalePartieActive || !instanceModalePartieActive) {
            ouvrirConfiguration(bouton);
            return;
        }

        const apresFermeture = () => {
            modalePartieActive.removeEventListener('hidden.bs.modal', apresFermeture);
            ouvrirConfiguration(bouton);
        };

        modalePartieActive.addEventListener('hidden.bs.modal', apresFermeture);
        instanceModalePartieActive.hide();
    };

    // Lie tous les boutons "Jouer" de la page.
    boutonsJouer.forEach((bouton) => {
        bouton.addEventListener('click', (event) => {
            event.preventDefault();

            const idPartieActive = parseInt(bouton.dataset.activePartieId || '0', 10);
            if (!(modalePartieActive instanceof HTMLElement)
                || !instanceModalePartieActive
                || !(boutonReprendre instanceof HTMLElement)
                || !(boutonSupprimer instanceof HTMLElement)
                || idPartieActive < 1
            ) {
                ouvrirConfiguration(bouton);
                return;
            }

            boutonEnAttente = bouton;
            urlSuppressionEnAttente = bouton.dataset.activePartieDeleteUrl || '';
            const nomTournoi = bouton.dataset.tournoiNom || 'ce tournoi';

            boutonReprendre.setAttribute('href', bouton.dataset.activePartieUrl || '#');
            if (messagePartieActive) {
                messagePartieActive.textContent = `Une partie est déjà en cours sur ${nomTournoi}. Tu veux la reprendre ?`;
            }

            instanceModalePartieActive.show();
        });
    });

    // Gère le bouton "recommencer" (suppression + ouverture config).
    if (boutonSupprimer instanceof HTMLElement) {
        boutonSupprimer.addEventListener('click', async () => {
            if (suppressionEnCours || !(boutonEnAttente instanceof HTMLElement)) {
                return;
            }

            if (urlSuppressionEnAttente === '') {
                masquerPartieActivePuisOuvrirConfig(boutonEnAttente);
                return;
            }

            suppressionEnCours = true;
            boutonSupprimer.setAttribute('disabled', 'disabled');
            const libelleAvant = boutonSupprimer.textContent;
            boutonSupprimer.textContent = 'Suppression...';

            try {
                const reponse = await fetch(urlSuppressionEnAttente, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!reponse.ok) {
                    throw new Error('Suppression impossible');
                }

                masquerPartieActivePuisOuvrirConfig(boutonEnAttente);
            } catch (erreur) {
                if (messagePartieActive) {
                    messagePartieActive.textContent = 'Suppression impossible. Réessaie dans quelques secondes.';
                }
            } finally {
                suppressionEnCours = false;
                boutonSupprimer.removeAttribute('disabled');
                boutonSupprimer.textContent = libelleAvant;
            }
        });
    }

    // Nettoyage global à la fermeture des modales.
    modale.addEventListener('hidden.bs.modal', () => {
        nettoyerArrieresPlansModales();
    });

    if (modalePartieActive instanceof HTMLElement) {
        modalePartieActive.addEventListener('hidden.bs.modal', () => {
            if (!suppressionEnCours) {
                boutonEnAttente = null;
                urlSuppressionEnAttente = '';
            }

            nettoyerArrieresPlansModales();
        });
    }
};

// Initialisation immédiate (chargement direct).
initialiserModaleJouerAccueil();

// Ré-initialisation à chaque navigation Turbo.
document.addEventListener('turbo:load', initialiserModaleJouerAccueil);
