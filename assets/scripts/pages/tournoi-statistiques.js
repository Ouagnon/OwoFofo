/**
 * Initialise la modale de lancement d'une partie depuis la page des statistiques.
 */
const initialiserModaleJouerStats = () => {
    const modale = document.getElementById('playTournamentModalStats');
    if (!modale) {
        return;
    }

    const modalePartieActive = document.getElementById('activePartieModalStats');

    // Empêche les doubles bindings d'événements avec Turbo.
    if (modale.dataset.playModalInit === '1') {
        return;
    }
    modale.dataset.playModalInit = '1';

    // Déplacement sous body pour stabiliser le stack des modales.
    if (modale.parentElement !== document.body) {
        document.body.appendChild(modale);
    }
    if (modalePartieActive && modalePartieActive.parentElement !== document.body) {
        document.body.appendChild(modalePartieActive);
    }

    const taillesStandards = [64, 32, 16, 8];

    // Références DOM principales.
    const formulaire = document.getElementById('playTournamentFormStats');
    const selectChoix = document.getElementById('home-play-choice-stats');
    const champCacheUtiliserTous = document.getElementById('home-play-utiliser-tous-hidden-stats');
    const champCacheTaille = document.getElementById('home-play-taille-hidden-stats');
    const boutonSoumettre = document.getElementById('playTournamentSubmitBtnStats');
    const boutonsJouer = document.querySelectorAll('.js-open-play-modal-stats');

    // Références de la modale de partie active.
    const messagePartieActive = document.getElementById('activePartieModalStatsMessage');
    const boutonReprendre = document.getElementById('activePartieResumeBtnStats');
    const boutonSupprimer = document.getElementById('activePartieDeleteBtnStats');

    if (!formulaire || !selectChoix || !champCacheUtiliserTous || !champCacheTaille || !boutonSoumettre) {
        return;
    }

    const modeleUrlConfig = modale.dataset.configTemplate || '';
    const instanceModaleConfig = window.bootstrap
        ? window.bootstrap.Modal.getOrCreateInstance(modale)
        : null;
    const instanceModalePartieActive = window.bootstrap && modalePartieActive
        ? window.bootstrap.Modal.getOrCreateInstance(modalePartieActive)
        : null;

    // État local pour gérer la suppression d'une partie active.
    let boutonEnAttente = null;
    let urlSuppressionEnAttente = '';
    let suppressionEnCours = false;

    const estTailleStandard = (valeur) => taillesStandards.includes(valeur);

    /** Calcule les tailles autorisées selon le mode de tournoi. */
    const construireTailles = (mode, total, compteA, compteB) => {
        const borneMax = mode === 'theme_vs_theme'
            ? Math.min(compteA, compteB) * 2
            : total;

        return taillesStandards.filter((valeur) => valeur <= borneMax);
    };

    const doitAfficherOptionUtiliserTous = (total) => !estTailleStandard(total);

    /** Rend les options du select de lancement. */
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

    /** Synchronise les champs cachés envoyés au contrôleur. */
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

    /** Hydrate la modale depuis le bouton stats cliqué. */
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

    /** Ouvre la modale de configuration. */
    const ouvrirConfiguration = (bouton) => {
        if (!(bouton instanceof HTMLElement)) {
            return;
        }

        hydraterDepuisBouton(bouton);
        if (instanceModaleConfig) {
            instanceModaleConfig.show();
            return;
        }

        if (formulaire.action) {
            window.location.href = formulaire.action;
        }
    };

    /** Nettoie les backdrops pour éviter un écran bloqué. */
    const nettoyerArrieresPlansModales = () => {
        if (document.querySelector('.modal.show')) {
            return;
        }

        document.querySelectorAll('.modal-backdrop').forEach((arrierePlan) => arrierePlan.remove());
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('padding-right');
    };

    /** Ferme la modale active puis ouvre la configuration. */
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

    // Gestion de tous les boutons "Jouer ce tournoi".
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

            boutonReprendre.setAttribute('href', bouton.dataset.activePartieUrl || '#');
            if (messagePartieActive) {
                messagePartieActive.textContent = 'Une partie est déjà en cours sur ce tournoi. Tu veux la reprendre ?';
            }

            instanceModalePartieActive.show();
        });
    });

    // Action "recommencer": suppression de la partie active.
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

/** Supprime l'overlay d'effets de victoire deja injecte. */
const nettoyerEffetsVictoireStats = (modaleResultat) => {
    if (!(modaleResultat instanceof HTMLElement)) {
        return;
    }

    modaleResultat.querySelectorAll('.stats-victory-overlay').forEach((overlay) => overlay.remove());
};

const valeurAleatoire = (minimum, maximum) => minimum + (Math.random() * (maximum - minimum));

/** Cree des confettis et feux d'artifice dans la modale resultat. */
const creerEffetsVictoireStats = (modaleResultat) => {
    if (!(modaleResultat instanceof HTMLElement)) {
        return;
    }

    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    const carte = modaleResultat.querySelector('.stats-popup-card');
    if (!(carte instanceof HTMLElement)) {
        return;
    }

    nettoyerEffetsVictoireStats(modaleResultat);

    const overlay = document.createElement('div');
    overlay.className = 'stats-victory-overlay';
    overlay.setAttribute('aria-hidden', 'true');

    const petitEcran = window.matchMedia && window.matchMedia('(max-width: 61.9375rem)').matches;
    const nombreConfettis = petitEcran ? 78 : 128;
    const nombreFeuxArtifice = petitEcran ? 7 : 11;
    const paletteConfettis = ['#ff4bb6', '#5dd8bc', '#ffe168', '#ff87d4', '#7dc9ff', '#ffffff'];

    for (let index = 0; index < nombreConfettis; index += 1) {
        const confetti = document.createElement('span');
        confetti.className = 'stats-victory-confetti';

        confetti.style.setProperty('--x-start', valeurAleatoire(1, 99).toFixed(2) + '%');
        confetti.style.setProperty('--drift', valeurAleatoire(-14, 14).toFixed(2) + 'rem');
        confetti.style.setProperty('--delay', valeurAleatoire(0, 1.05).toFixed(2) + 's');
        confetti.style.setProperty('--duration', valeurAleatoire(2.8, 4.2).toFixed(2) + 's');
        confetti.style.setProperty('--rotate-start', valeurAleatoire(0, 360).toFixed(1) + 'deg');
        confetti.style.setProperty('--rotate-end', valeurAleatoire(-440, 440).toFixed(1) + 'deg');
        confetti.style.setProperty('--size', valeurAleatoire(petitEcran ? 0.28 : 0.34, petitEcran ? 0.54 : 0.72).toFixed(2) + 'rem');
        confetti.style.setProperty('--color', paletteConfettis[Math.floor(Math.random() * paletteConfettis.length)]);

        overlay.appendChild(confetti);
    }

    for (let index = 0; index < nombreFeuxArtifice; index += 1) {
        const feu = document.createElement('span');
        feu.className = 'stats-victory-firework';

        feu.style.setProperty('--x', valeurAleatoire(8, 92).toFixed(2) + '%');
        feu.style.setProperty('--y', valeurAleatoire(6, 72).toFixed(2) + '%');
        feu.style.setProperty('--hue', String(Math.round(valeurAleatoire(0, 360))));
        feu.style.setProperty('--delay', valeurAleatoire(0.08, 1.2).toFixed(2) + 's');
        feu.style.setProperty('--duration', valeurAleatoire(1.5, 2.25).toFixed(2) + 's');
        feu.style.setProperty('--size', valeurAleatoire(petitEcran ? 0.22 : 0.27, petitEcran ? 0.33 : 0.4).toFixed(2) + 'rem');

        overlay.appendChild(feu);
    }

    carte.appendChild(overlay);
};

/**
 * Lance automatiquement la modale résultat si une partie vient d'être terminée.
 */
const initialiserModaleResultatStats = () => {
    const modaleResultat = document.getElementById('statsResultModal');
    if (!modaleResultat || modaleResultat.dataset.modalInit === '1') {
        return;
    }

    modaleResultat.dataset.modalInit = '1';
    if (modaleResultat.parentElement !== document.body) {
        document.body.appendChild(modaleResultat);
    }

    if (window.bootstrap) {
        const instanceModale = window.bootstrap.Modal.getOrCreateInstance(modaleResultat);

        // Relance proprement l'animation de victoire à chaque affichage.
        const jouerAnimationVictoire = () => {
            modaleResultat.classList.remove('is-victory');
            nettoyerEffetsVictoireStats(modaleResultat);
            void modaleResultat.offsetWidth;
            modaleResultat.classList.add('is-victory');
            creerEffetsVictoireStats(modaleResultat);
        };

        modaleResultat.addEventListener('shown.bs.modal', jouerAnimationVictoire);
        instanceModale.show();

        // Au clic fermer, on revient sur la page stats propre sans query spéciale.
        modaleResultat.addEventListener('hidden.bs.modal', () => {
            nettoyerEffetsVictoireStats(modaleResultat);
            const urlRedirection = modaleResultat.dataset.redirectUrl || '';
            if (urlRedirection !== '') {
                window.location.href = urlRedirection;
            }
        });
    }
};

/** Remplace les aperçus vidéo par un iframe au clic pour éviter de charger tous les lecteurs d'un coup. */
const initialiserApercusVideoStats = () => {
    const boutonsLecture = document.querySelectorAll('.js-stats-load-video');
    if (boutonsLecture.length === 0) {
        return;
    }

    boutonsLecture.forEach((bouton) => {
        if (!(bouton instanceof HTMLButtonElement) || bouton.dataset.videoInit === '1') {
            return;
        }

        bouton.dataset.videoInit = '1';

        bouton.addEventListener('click', () => {
            const urlVideo = bouton.dataset.videoUrl || '';
            if (urlVideo === '') {
                return;
            }

            let urlLecture = urlVideo;
            try {
                const urlNormalisee = new URL(urlVideo, window.location.origin);
                urlNormalisee.searchParams.set('autoplay', '1');
                urlLecture = urlNormalisee.toString();
            } catch (erreur) {
                urlLecture = urlVideo + (urlVideo.includes('?') ? '&' : '?') + 'autoplay=1';
            }

            const titreVideo = bouton.dataset.videoTitle || 'Video';
            const iframe = document.createElement('iframe');
            iframe.src = urlLecture;
            iframe.title = titreVideo;
            iframe.loading = 'lazy';
            iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
            iframe.referrerPolicy = 'strict-origin-when-cross-origin';
            iframe.setAttribute('allowfullscreen', '');

            const conteneur = document.createElement('div');
            conteneur.className = 'stats-video-embed';
            conteneur.appendChild(iframe);

            bouton.replaceWith(conteneur);
        });
    });
};

/** Initialise tous les comportements de la page statistiques. */
const initialiserPageStatistiquesTournoi = () => {
    initialiserModaleJouerStats();
    initialiserModaleResultatStats();
    initialiserApercusVideoStats();
};

initialiserPageStatistiquesTournoi();
document.addEventListener('turbo:load', initialiserPageStatistiquesTournoi);
