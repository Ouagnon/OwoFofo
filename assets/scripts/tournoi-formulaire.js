const initialiserWizardTournoi = () => {
    const racineWizard = document.getElementById('tournoi-wizard');
    if (!racineWizard) {
        return;
    }

    if (racineWizard.dataset.jsInitialise === '1') {
        return;
    }
    racineWizard.dataset.jsInitialise = '1';

    const urlUploadImage = racineWizard.dataset.uploadUrl || '';
    const urlUpsertElement = racineWizard.dataset.upsertElementUrl || '';
    const urlDeleteElement = racineWizard.dataset.deleteElementUrl || '';
    const urlProfilJoueur = racineWizard.dataset.profileUrl || '';
    const autosaveElementsActif = racineWizard.dataset.autosaveElements === '1';
    const modeInitial = (racineWizard.dataset.modeValue || '').trim();
    const tournoiIdInitialTexte = (racineWizard.dataset.tournoiId || '').trim();
    const tournoiIdInitial = Number.parseInt(tournoiIdInitialTexte, 10);

    const idChampMode = racineWizard.dataset.modeId || '';
    const idChampNom = racineWizard.dataset.nomId || '';
    const idChampDescription = racineWizard.dataset.descriptionId || '';
    const idChampCover = racineWizard.dataset.coverId || '';
    const idChampThemeA = racineWizard.dataset.themeAId || '';
    const idChampThemeB = racineWizard.dataset.themeBId || '';
    const idChampElements = racineWizard.dataset.elementsId || '';

    const champMode = document.getElementById(idChampMode);
    const champNom = document.getElementById(idChampNom);
    const champDescription = document.getElementById(idChampDescription);
    const champCover = document.getElementById(idChampCover);
    const champThemeA = document.getElementById(idChampThemeA);
    const champThemeB = document.getElementById(idChampThemeB);
    const champElementsPayload = document.getElementById(idChampElements);

    if (
        champMode
        && champMode.value.trim() === ''
        && ['libre', 'theme_vs_theme'].includes(modeInitial)
    ) {
        champMode.value = modeInitial;
    }

    const formulaire = document.getElementById('tournoi-wizard-form');
    const champIntentSoumission = document.getElementById('submit-intent');

    const onglets = Array.from(racineWizard.querySelectorAll('[data-tab-target]'));
    const panneaux = Array.from(racineWizard.querySelectorAll('[data-tab-panel]'));

    const boutonAllerElements = document.getElementById('btn-go-elements');
    const boutonRetourTournoi = document.getElementById('btn-back-tournoi');
    const boutonRetourAccueil = document.getElementById('btn-back-home');

    const blocThemeA = document.getElementById('themes-container-a');
    const blocThemeB = document.getElementById('themes-container-b');
    const ligneThemes = document.getElementById('themes-row');

    const imageCover = document.getElementById('cover-preview-image');
    const placeholderCover = document.getElementById('cover-preview-placeholder');
    const coverUploadBox = document.getElementById('cover-upload-box');
    const coverUploadInput = document.getElementById('cover-upload-input');
    const coverUploadTrigger = document.getElementById('cover-upload-trigger');
    const coverUploadStatus = document.getElementById('cover-upload-status');

    const cartesTypeMedia = Array.from(document.querySelectorAll('[data-media-choice]'));
    const blocImage = document.getElementById('choice-image-group');
    const blocVideo = document.getElementById('choice-video-group');

    const champImageUrl = document.getElementById('choice-image-url');
    const choiceImageUploadBox = document.getElementById('choice-image-upload-box');
    const choiceImageUploadInput = document.getElementById('choice-image-upload-input');
    const choiceImageUploadTrigger = document.getElementById('choice-image-upload-trigger');
    const choiceImageUploadStatus = document.getElementById('choice-image-upload-status');
    const choiceImagePreviewImage = document.getElementById('choice-image-preview-image');
    const choiceImagePreviewPlaceholder = document.getElementById('choice-image-preview-placeholder');
    const champVideoUrl = document.getElementById('choice-video-url');
    const choiceVideoPreviewFrame = document.getElementById('choice-video-preview-frame');
    const choiceVideoPreviewPlaceholder = document.getElementById('choice-video-preview-placeholder');
    const champChoiceTitle = document.getElementById('choice-title');
    const blocChoiceTheme = document.getElementById('choice-theme-group');
    const choiceThemeRadioA = document.getElementById('choice-theme-a');
    const choiceThemeRadioB = document.getElementById('choice-theme-b');
    const choiceThemeLabelA = document.getElementById('choice-theme-a-label');
    const choiceThemeLabelB = document.getElementById('choice-theme-b-label');

    // Expose les radios de theme via une API de type value.
    const champChoiceTheme = {
        get value() {
            // Retourne le slot actif selon l etat des radios.
            if (choiceThemeRadioA.checked) return 'A';
            if (choiceThemeRadioB.checked) return 'B';
            return '';
        },
        set value(val) {
            // Maintient les deux radios synchronisees lors d une affectation.
            if (val === 'A') {
                choiceThemeRadioA.checked = true;
            } else if (val === 'B') {
                choiceThemeRadioB.checked = true;
            } else {
                choiceThemeRadioA.checked = false;
                choiceThemeRadioB.checked = false;
            }
        }
    };

    const boutonAjouter = document.getElementById('btn-add-choice');
    const listeChoices = document.getElementById('choices-list');
    const compteurChoices = document.getElementById('choices-counter');

    const zonePublish = document.getElementById('publish-zone');
    const boutonPublier = document.getElementById('btn-publish');
    const hintPublier = document.getElementById('publish-hint');
    const boutonSauvegarderBrouillon = document.getElementById('btn-save-draft');

    const pagination = document.getElementById('choices-pagination');
    const boutonPagePrecedente = document.getElementById('btn-page-prev');
    const boutonPageSuivante = document.getElementById('btn-page-next');
    const indicateurPage = document.getElementById('choices-page-indicator');

    if (
        champMode
        && champNom
        && champDescription
        && champCover
        && champThemeA
        && champThemeB
        && champElementsPayload
        && formulaire
        && champIntentSoumission
        && boutonAllerElements
        && boutonRetourTournoi
        && blocThemeA
        && blocThemeB
        && ligneThemes
        && imageCover
        && placeholderCover
        && coverUploadBox
        && coverUploadInput
        && coverUploadTrigger
        && coverUploadStatus
        && blocImage
        && blocVideo
        && champImageUrl
        && choiceImageUploadBox
        && choiceImageUploadInput
        && choiceImageUploadTrigger
        && choiceImageUploadStatus
        && choiceImagePreviewImage
        && choiceImagePreviewPlaceholder
        && champVideoUrl
        && choiceVideoPreviewFrame
        && choiceVideoPreviewPlaceholder
        && champChoiceTitle
        && blocChoiceTheme
        && choiceThemeRadioA
        && choiceThemeRadioB
        && choiceThemeLabelA
        && choiceThemeLabelB
        && boutonAjouter
        && listeChoices
        && compteurChoices
        && zonePublish
        && boutonPublier
        && pagination
        && boutonPagePrecedente
        && boutonPageSuivante
        && indicateurPage
    ) {
        const TAILLE_PAGE = 10;
        const MAX_ELEMENTS = 64;
        const MIN_POUR_PUBLIER = 8;

        const etat = {
            ongletActif: 'tournoi',
            mediaActif: 'image',
            editionInlineId: null,
            editionImageInlineId: null,
            pageCourante: 1,
            tournoiId: Number.isInteger(tournoiIdInitial) && tournoiIdInitial > 0 ? tournoiIdInitial : null,
            elements: [],
        };

        let soumissionValidee = false;
        let operationElementEnCours = false;
        const actionFormulaireInitiale = formulaire.getAttribute('action') || '';

        const blocFeedback = document.createElement('div');
        blocFeedback.className = 'd-none';
        formulaire.prepend(blocFeedback);

        // Affiche un message d erreur bloquant en haut du formulaire.
        const afficherErreur = (message) => {
            blocFeedback.className = 'alert alert-danger mb-3';
            blocFeedback.textContent = message;
        };

        // Masque la zone de feedback quand il n y a plus d erreur.
        const cacherErreur = () => {
            blocFeedback.className = 'd-none';
            blocFeedback.textContent = '';
        };

        // Met a jour le statut d upload et son etat visuel.
        const afficherUploadStatut = (element, texte, estErreur = false) => {
            element.textContent = texte;
            element.classList.toggle('upload-status-error', estErreur);
        };

        // Memorise l intention de soumission pour distinguer publier/brouillon.
        const definirIntentSoumission = (intent) => {
            champIntentSoumission.value = intent;
        };

        // Limite la retention automatique des champs par le navigateur.
        const desactiverRetentionNavigateur = () => {
            formulaire.setAttribute('autocomplete', 'off');

            racineWizard.querySelectorAll('input, textarea, select').forEach((champ) => {
                champ.setAttribute('autocomplete', 'off');
            });
        };

        // Purge l etat du brouillon quand l utilisateur quitte sans publier.
        const purgerBrouillonLocal = () => {
            if (soumissionValidee) {
                return;
            }

            formulaire.reset();

            etat.elements = [];
            etat.editionInlineId = null;
            etat.editionImageInlineId = null;
            etat.pageCourante = 1;
            etat.mediaActif = 'image';
            etat.tournoiId = Number.isInteger(tournoiIdInitial) && tournoiIdInitial > 0 ? tournoiIdInitial : null;

            if (actionFormulaireInitiale !== '') {
                formulaire.setAttribute('action', actionFormulaireInitiale);
            }

            champCover.value = '';
            champElementsPayload.value = '[]';
            champImageUrl.value = '';
            champVideoUrl.value = '';
            champChoiceTitle.value = '';
            champChoiceTheme.value = '';
            definirIntentSoumission('publish');

            afficherUploadStatut(coverUploadStatus, '');
            afficherUploadStatut(choiceImageUploadStatus, '');
            cacherErreur();

            mettreAJourPreviewCover();
            mettreAJourPreviewImageChoice();
            mettreAJourVisibiliteThemes();
            definirTypeMediaActif('image');
            rafraichirDisponibiliteOnglets();
            renderListe();
            afficherOnglet('tournoi');
        };

        // Conserve l'image d'origine pour éviter toute perte de qualité.
        const preparerFichierImage = async (fichier) => {
            return fichier;
        };

        // Televerse un fichier image valide vers l endpoint backend.
        const televerserImage = async (fichier) => {
            if (!urlUploadImage) {
                throw new Error('Route d\'upload indisponible.');
            }

            const typeValide = ['image/png', 'image/jpeg', 'image/webp', 'image/gif'].includes(fichier.type);
            if (!typeValide) {
                throw new Error('Format d\'image non supporté.');
            }

            if (fichier.size > 12 * 1024 * 1024) {
                throw new Error('Image trop lourde (12MB max).');
            }

            const formData = new FormData();
            formData.append('image', fichier);

            const reponse = await fetch(urlUploadImage, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            // Parse le JSON de facon defensive pour eviter un crash UI.
            const donnees = await reponse.json().catch(() => ({}));
            if (!reponse.ok || !donnees.url) {
                throw new Error(typeof donnees.error === 'string' ? donnees.error : 'Échec de l\'upload de l\'image.');
            }

            return donnees.url;
        };

        // Execute une requete JSON POST et remonte les erreurs metier.
        const posterJson = async (url, payload) => {
            if (!url) {
                throw new Error('Route serveur indisponible.');
            }

            const reponse = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            });

            const donnees = await reponse.json().catch(() => ({}));
            if (!reponse.ok) {
                throw new Error(typeof donnees.error === 'string' ? donnees.error : 'Erreur serveur.');
            }

            return donnees;
        };

        // Capture les donnees du tournoi pour l autosauvegarde des elements.
        const construirePayloadTournoi = () => {
            return {
                nom: champNom.value.trim(),
                description: champDescription.value.trim(),
                coverImageUrl: champCover.value.trim(),
                mode: obtenirModeTournoiCourant(),
                nomThemeA: champThemeA.value.trim(),
                nomThemeB: champThemeB.value.trim(),
            };
        };

        // Injecte l id de tournoi cree cote serveur et bascule l action du formulaire en mode edition.
        const ajusterContexteTournoiPersistant = (donneesServeur) => {
            const idTexte = String(donneesServeur.tournoiId || '').trim();
            const tournoiId = Number.parseInt(idTexte, 10);

            if (!Number.isInteger(tournoiId) || tournoiId <= 0) {
                return;
            }

            etat.tournoiId = tournoiId;

            const editUrl = typeof donneesServeur.editUrl === 'string' ? donneesServeur.editUrl.trim() : '';
            if (editUrl === '') {
                return;
            }

            formulaire.setAttribute('action', editUrl);

            if (window.history && typeof window.history.replaceState === 'function') {
                window.history.replaceState({}, '', editUrl);
            }
        };

        // Normalise la reponse serveur pour conserver un objet element compatible avec le rendu local.
        const normaliserElementDepuisServeur = (elementLocal, elementServeur) => {
            return {
                id: String(elementServeur.id || elementLocal.id),
                title: String(elementServeur.title || elementLocal.title),
                mediaType: elementServeur.mediaType === 'video' ? 'video' : 'image',
                url: String(elementServeur.url || elementLocal.url),
                videoSource: String(elementServeur.videoSource || elementLocal.videoSource || ''),
                theme: ['A', 'B'].includes(String(elementServeur.theme || '')) ? String(elementServeur.theme) : (elementLocal.theme || ''),
                startTime: Number.isInteger(elementServeur.startTime) ? elementServeur.startTime : elementLocal.startTime,
                endTime: Number.isInteger(elementServeur.endTime) ? elementServeur.endTime : elementLocal.endTime,
            };
        };

        // Sauvegarde immediatement l element en base (creation ou modification).
        const sauvegarderElementEnBase = async (elementLocal) => {
            if (!autosaveElementsActif) {
                return elementLocal;
            }

            const payload = {
                tournoiId: etat.tournoiId,
                tournoi: construirePayloadTournoi(),
                element: {
                    id: elementLocal.id,
                    title: elementLocal.title,
                    mediaType: elementLocal.mediaType,
                    url: elementLocal.url,
                    theme: elementLocal.theme,
                    startTime: elementLocal.startTime,
                    endTime: elementLocal.endTime,
                    videoSource: elementLocal.videoSource,
                },
            };

            const donnees = await posterJson(urlUpsertElement, payload);
            ajusterContexteTournoiPersistant(donnees);

            if (!donnees.element || typeof donnees.element !== 'object') {
                throw new Error('Réponse serveur invalide pendant la sauvegarde de l\'élément.');
            }

            return normaliserElementDepuisServeur(elementLocal, donnees.element);
        };

        // Supprime immediatement un element en base.
        const supprimerElementEnBase = async (idElement) => {
            if (!autosaveElementsActif) {
                return;
            }

            if (etat.tournoiId === null) {
                return;
            }

            await posterJson(urlDeleteElement, {
                tournoiId: etat.tournoiId,
                elementId: idElement,
            });
        };

        // Echappe le texte utilisateur avant injection HTML.
        const escapeHtml = (texte) => {
            return String(texte)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        };

        // Retourne un nom de theme lisible depuis le slot A/B.
        const obtenirNomTheme = (slot) => {
            if (slot === 'A') {
                return champThemeA.value.trim() || 'Thème A';
            }

            if (slot === 'B') {
                return champThemeB.value.trim() || 'Thème B';
            }

            return 'Sans thème';
        };

        // Retourne le mode du tournoi avec fallback sur la valeur initiale.
        const obtenirModeTournoiCourant = () => {
            const modeChamp = (champMode.value || '').trim();

            if (modeChamp !== '') {
                return modeChamp;
            }

            return ['libre', 'theme_vs_theme'].includes(modeInitial) ? modeInitial : '';
        };

        // Indique si le mode courant est Theme VS Theme.
        const estModeTheme = () => {
            return obtenirModeTournoiCourant() === 'theme_vs_theme';
        };

        // Verifie les champs requis avant acces a l onglet Elements.
        const champsCoverComplets = () => {
            const nomOk = champNom.value.trim().length >= 3;
            const descriptionOk = champDescription.value.trim().length > 0;
            const coverValeur = champCover.value.trim();
            const coverOk = coverValeur !== '' && coverValeur.startsWith('/') && coverValeur.includes('/uploads/tournois/');
            const modeOk = ['libre', 'theme_vs_theme'].includes(obtenirModeTournoiCourant());

            if (!nomOk || !descriptionOk || !coverOk || !modeOk) {
                return false;
            }

            if (estModeTheme()) {
                // En mode VS, les deux themes doivent etre renseignes et differents.
                const themeA = champThemeA.value.trim();
                const themeB = champThemeB.value.trim();

                if (themeA === '' || themeB === '') {
                    return false;
                }

                return themeA.toLowerCase() !== themeB.toLowerCase();
            }

            return true;
        };

        // Compte les elements affectes a chaque slot de theme.
        const compterParTheme = () => {
            let compteA = 0;
            let compteB = 0;

            etat.elements.forEach((element) => {
                if (element.theme === 'A') {
                    compteA += 1;
                }
                if (element.theme === 'B') {
                    compteB += 1;
                }
            });

            return { compteA, compteB };
        };

        // Determine si les contraintes de publication sont respectees.
        const peutPublier = () => {
            if (etat.elements.length < MIN_POUR_PUBLIER) {
                return false;
            }

            if (!estModeTheme()) {
                return true;
            }

            const { compteA, compteB } = compterParTheme();
            return Math.min(compteA, compteB) >= MIN_POUR_PUBLIER;
        };

        // Serialize les elements courants dans le champ cache pour Symfony.
        const synchroniserPayload = () => {
            const payload = etat.elements.map((element) => ({
                id: element.id,
                title: element.title,
                mediaType: element.mediaType,
                url: element.url,
                theme: element.theme,
                startTime: element.startTime,
                endTime: element.endTime,
            }));

            champElementsPayload.value = JSON.stringify(payload);
        };

        // Construit la source de miniature (image ou preview YouTube).
        const construireMiniature = (element) => {
            if (element.mediaType === 'video') {
                const idYoutube = extraireYoutubeId(element.url || element.videoSource || '');
                if (idYoutube) {
                    return `https://img.youtube.com/vi/${idYoutube}/hqdefault.jpg`;
                }
            }

            return element.url;
        };

        // Retourne le type media selectionne dans la carte inline.
        const obtenirTypeMediaInlineCarte = (carte, typeFallback = 'image') => {
            if (!(carte instanceof HTMLElement)) {
                return typeFallback;
            }

            const radioActif = carte.querySelector('[data-inline-field="mediaTypeRadio"]:checked');
            if (radioActif instanceof HTMLInputElement && ['image', 'video'].includes(radioActif.value)) {
                return radioActif.value;
            }

            return typeFallback;
        };

        // Met a jour l apercu image de la carte inline depuis le media cache.
        const mettreAJourPreviewImageInlineCarte = (carte) => {
            if (!(carte instanceof HTMLElement)) {
                return;
            }

            const coucheImage = carte.querySelector('[data-inline-preview-layer="image"]');
            const coucheVideo = carte.querySelector('[data-inline-preview-layer="video"]');
            const blocImageInline = carte.querySelector('[data-inline-media-block="image"]');
            const champUrl = blocImageInline instanceof HTMLElement
                ? blocImageInline.querySelector('[data-inline-field="mediaUrl"]')
                : null;
            const imagePreview = carte.querySelector('[data-inline-field="imagePreview"]');
            const imagePreviewPlaceholder = carte.querySelector('[data-inline-field="imagePreviewPlaceholder"]');

            if (!(champUrl instanceof HTMLInputElement)
                || !(imagePreview instanceof HTMLImageElement)
                || !(imagePreviewPlaceholder instanceof HTMLElement)) {
                return;
            }

            if (coucheImage instanceof HTMLElement) {
                coucheImage.classList.remove('d-none');
            }
            if (coucheVideo instanceof HTMLElement) {
                coucheVideo.classList.add('d-none');
            }

            const valeur = champUrl.value.trim();
            if (valeur === '') {
                imagePreview.classList.add('d-none');
                imagePreview.setAttribute('src', '');
                imagePreviewPlaceholder.classList.remove('d-none');
            } else {
                imagePreview.classList.remove('d-none');
                imagePreview.setAttribute('src', valeur);
                imagePreviewPlaceholder.classList.add('d-none');
            }
        };

        // Met a jour l apercu video de la carte inline depuis les champs YouTube.
        const mettreAJourPreviewVideoInlineCarte = (carte) => {
            if (!(carte instanceof HTMLElement)) {
                return;
            }

            const coucheImage = carte.querySelector('[data-inline-preview-layer="image"]');
            const coucheVideo = carte.querySelector('[data-inline-preview-layer="video"]');
            const blocVideoInline = carte.querySelector('[data-inline-media-block="video"]');
            const champUrl = blocVideoInline instanceof HTMLElement
                ? blocVideoInline.querySelector('[data-inline-field="mediaUrl"]')
                : null;
            const videoPreviewFrame = carte.querySelector('[data-inline-field="videoPreviewFrame"]');
            const videoPreviewPlaceholder = carte.querySelector('[data-inline-field="videoPreviewPlaceholder"]');

            if (!(champUrl instanceof HTMLInputElement)
                || !(videoPreviewFrame instanceof HTMLIFrameElement)
                || !(videoPreviewPlaceholder instanceof HTMLElement)) {
                return;
            }

            if (coucheImage instanceof HTMLElement) {
                coucheImage.classList.add('d-none');
            }
            if (coucheVideo instanceof HTMLElement) {
                coucheVideo.classList.remove('d-none');
            }

            const urlEmbed = construireUrlYoutubeEmbed(champUrl.value);
            if (!urlEmbed) {
                videoPreviewFrame.classList.add('d-none');
                videoPreviewFrame.setAttribute('src', '');
                videoPreviewPlaceholder.classList.remove('d-none');
                return;
            }

            videoPreviewFrame.classList.remove('d-none');
            videoPreviewFrame.setAttribute('src', urlEmbed);
            videoPreviewPlaceholder.classList.add('d-none');
        };

        // Gere l upload image inline depuis la miniature de carte.
        const televerserImageInlineCarte = async (carte, fichier) => {
            if (!(carte instanceof HTMLElement) || !(fichier instanceof File)) {
                return;
            }

            const statutUpload = carte.querySelector('[data-inline-field="uploadStatus"]');
            const blocImageInline = carte.querySelector('[data-inline-media-block="image"]');
            const champMediaInline = blocImageInline instanceof HTMLElement
                ? blocImageInline.querySelector('[data-inline-field="mediaUrl"]')
                : null;

            if (!(champMediaInline instanceof HTMLInputElement)) {
                return;
            }

            const definirStatutInline = (texte, estErreur = false) => {
                if (statutUpload instanceof HTMLElement) {
                    afficherUploadStatut(statutUpload, texte, estErreur);
                }
            };

            if (operationElementEnCours) {
                return;
            }

            try {
                cacherErreur();
                operationElementEnCours = true;
                definirStatutInline('Upload...');

                const fichierAEnvoyer = await preparerFichierImage(fichier);
                const urlImage = await televerserImage(fichierAEnvoyer);

                champMediaInline.value = urlImage;
                mettreAJourPreviewImageInlineCarte(carte);
                definirStatutInline('Image prete.');
            } catch (erreur) {
                definirStatutInline(
                    erreur instanceof Error ? erreur.message : 'Echec de l\'upload.',
                    true
                );
                afficherErreur(erreur instanceof Error ? erreur.message : 'Erreur pendant l\'upload de l\'image.');
            } finally {
                operationElementEnCours = false;
            }
        };

        // Ouvre le selecteur de fichier image de la carte inline ciblee.
        const ouvrirSelecteurImageInlineCarte = (idElement) => {
            const carte = Array.from(listeChoices.querySelectorAll('.choice-card'))
                .find((item) => item.getAttribute('data-choice-id') === idElement);

            if (!(carte instanceof HTMLElement)) {
                return;
            }

            const champFichierInline = carte.querySelector('[data-inline-field="imageFile"]');
            if (champFichierInline instanceof HTMLInputElement) {
                champFichierInline.click();
            }
        };

        // Synchronise les blocs visuels de l editeur inline (type + media).
        const mettreAJourFormulaireInlineCarte = (carte) => {
            if (!(carte instanceof HTMLElement)) {
                return;
            }

            const typeActif = obtenirTypeMediaInlineCarte(carte, 'image');

            carte.querySelectorAll('[data-inline-media-option]').forEach((option) => {
                if (!(option instanceof HTMLElement)) {
                    return;
                }

                option.classList.toggle('is-active', option.getAttribute('data-inline-media-option') === typeActif);
            });

            carte.querySelectorAll('[data-inline-media-block]').forEach((bloc) => {
                if (!(bloc instanceof HTMLElement)) {
                    return;
                }

                const typeBloc = bloc.getAttribute('data-inline-media-block');
                bloc.classList.toggle('d-none', typeBloc !== typeActif);
            });

            if (typeActif === 'image') {
                mettreAJourPreviewImageInlineCarte(carte);
            }

            if (typeActif === 'video') {
                mettreAJourPreviewVideoInlineCarte(carte);
            }
        };

        // Rend la liste paginee des elements puis synchronise le payload.
        const renderListe = () => {
            const total = etat.elements.length;
            const totalPages = Math.max(1, Math.ceil(total / TAILLE_PAGE));
            etat.pageCourante = Math.min(etat.pageCourante, totalPages);
            etat.pageCourante = Math.max(1, etat.pageCourante);

            compteurChoices.textContent = `${total} / ${MAX_ELEMENTS}`;

            if (total === 0) {
                listeChoices.innerHTML = '<div class="choices-empty">Aucun element ajoute pour le moment.</div>';
            } else {
                // Calcule la tranche de page courante avant generation du HTML.
                const debut = (etat.pageCourante - 1) * TAILLE_PAGE;
                const fin = debut + TAILLE_PAGE;
                const elementsAffiches = etat.elements.slice().reverse().slice(debut, fin);

                listeChoices.innerHTML = elementsAffiches.map((element) => {
                    const estEnEdition = etat.editionInlineId === element.id;
                    const editionImageActive = estEnEdition && etat.editionImageInlineId === element.id;
                    const miniature = construireMiniature(element);
                    const badgeTheme = estModeTheme()
                        ? `<span class="choice-theme-badge">${escapeHtml(obtenirNomTheme(element.theme))}</span>`
                        : '';

                    const blocLecture = `
                        <h3 class="choice-card-title">${escapeHtml(element.title)}</h3>
                        <div class="choice-card-meta">
                            <span class="choice-type-tag">${element.mediaType === 'video' ? 'Video' : 'Image'}</span>
                            ${badgeTheme}
                        </div>
                    `;

                    const nomThemeInline = `inline-theme-slot-${escapeHtml(element.id)}`;
                    const optionsTheme = estModeTheme()
                        ? `
                            <div class="col-12 col-lg-3">
                                <label class="form-label form-label-sm mb-1">Thème</label>
                                <div class="theme-choice-buttons">
                                    <div class="form-check">
                                        <input
                                            type="radio"
                                            name="${nomThemeInline}"
                                            value="A"
                                            class="form-check-input"
                                            data-inline-field="themeRadio"
                                            ${element.theme === 'A' ? 'checked' : ''}
                                        >
                                        <label class="form-check-label">${escapeHtml(champThemeA.value.trim() || 'Thème A')}</label>
                                    </div>
                                    <div class="form-check">
                                        <input
                                            type="radio"
                                            name="${nomThemeInline}"
                                            value="B"
                                            class="form-check-input"
                                            data-inline-field="themeRadio"
                                            ${element.theme === 'B' ? 'checked' : ''}
                                        >
                                        <label class="form-check-label">${escapeHtml(champThemeB.value.trim() || 'Thème B')}</label>
                                    </div>
                                </div>
                            </div>
                        `
                        : '';

                    const nomTypeInline = `inline-media-type-${escapeHtml(element.id)}`;
                    const urlImageInitialeInline = element.mediaType === 'image' ? element.url : '';
                    const urlVideoInitialeInline = element.mediaType === 'video' ? (element.videoSource || element.url) : '';
                    const urlVideoEmbedInitialeInline = element.mediaType === 'video'
                        ? construireUrlYoutubeEmbed(urlVideoInitialeInline)
                        : null;

                    const blocTypeEdition = `
                        <div class="col-12 col-lg-5">
                            <label class="form-label form-label-sm mb-1">Type</label>
                            <div class="inline-media-choices">
                                <label class="inline-media-option ${element.mediaType === 'image' ? 'is-active' : ''}" data-inline-media-option="image">
                                    <input
                                        type="radio"
                                        class="d-none"
                                        name="${nomTypeInline}"
                                        data-inline-field="mediaTypeRadio"
                                        value="image"
                                        ${element.mediaType === 'image' ? 'checked' : ''}
                                    >
                                    <span class="inline-media-option-icon">▣</span>
                                    <span>Image</span>
                                </label>
                                <label class="inline-media-option ${element.mediaType === 'video' ? 'is-active' : ''}" data-inline-media-option="video">
                                    <input
                                        type="radio"
                                        class="d-none"
                                        name="${nomTypeInline}"
                                        data-inline-field="mediaTypeRadio"
                                        value="video"
                                        ${element.mediaType === 'video' ? 'checked' : ''}
                                    >
                                    <span class="inline-media-option-icon">▶</span>
                                    <span>Video</span>
                                </label>
                            </div>
                        </div>
                    `;

                    const blocMediaEdition = `
                        <div class="col-12 ${element.mediaType === 'image' ? '' : 'd-none'} inline-image-edit-hidden" data-inline-media-block="image">
                            <input type="hidden" data-inline-field="mediaUrl" value="${escapeHtml(urlImageInitialeInline)}">
                            <input type="file" class="d-none" accept="image/png,image/jpeg,image/webp,image/gif" data-inline-field="imageFile">
                            <span class="upload-status" data-inline-field="uploadStatus"></span>
                        </div>

                        <div class="col-12 ${element.mediaType === 'video' ? '' : 'd-none'}" data-inline-media-block="video">
                            <div class="row g-2">
                                <div class="col-12 col-lg-8">
                                    <label class="form-label form-label-sm mb-1">URL YouTube</label>
                                    <input
                                        type="url"
                                        class="form-control form-control-sm"
                                        data-inline-field="mediaUrl"
                                        value="${escapeHtml(urlVideoInitialeInline)}"
                                        placeholder="https://www.youtube.com/watch?v=..."
                                    >
                                </div>
                            </div>
                        </div>
                    `;

                    const blocEdition = `
                        <div class="choice-inline-editor">
                            <div class="row g-2">
                                <div class="col-12 col-lg-4">
                                    <label class="form-label form-label-sm mb-1">Nom</label>
                                    <input
                                        type="text"
                                        maxlength="255"
                                        class="form-control form-control-sm"
                                        data-inline-field="title"
                                        value="${escapeHtml(element.title)}"
                                    >
                                </div>
                                ${blocTypeEdition}
                                ${optionsTheme}
                                ${blocMediaEdition}
                            </div>
                        </div>
                    `;

                    const actions = estEnEdition
                        ? `
                            <button type="button" class="btn btn-sm btn-outline-light ${element.mediaType === 'image' ? '' : 'd-none'}" data-action="inline-image-trigger" data-choice-id="${escapeHtml(element.id)}">Modifier l image</button>
                            <button type="button" class="btn btn-sm btn-danger" data-action="save-inline" data-choice-id="${escapeHtml(element.id)}">Enregistrer</button>
                            <button type="button" class="btn btn-sm btn-outline-light" data-action="cancel-inline" data-choice-id="${escapeHtml(element.id)}">Annuler</button>
                        `
                        : `
                            <button type="button" class="btn btn-sm btn-outline-light" data-action="edit" data-choice-id="${escapeHtml(element.id)}">Modifier</button>
                            <button type="button" class="btn btn-sm btn-danger" data-action="delete" data-choice-id="${escapeHtml(element.id)}">Supprimer</button>
                        `;

                    return `
                        <article class="choice-card ${estEnEdition ? 'is-inline-editing' : ''}" data-choice-id="${escapeHtml(element.id)}">
                            <div
                                class="choice-card-thumb-wrap ${editionImageActive ? 'is-inline-image-drop-enabled' : ''} ${estEnEdition && element.mediaType === 'video' ? 'is-inline-video-preview' : ''}"
                                data-inline-thumb-zone
                                data-choice-id="${escapeHtml(element.id)}"
                            >
                                <div class="choice-card-preview-layer ${element.mediaType === 'video' && estEnEdition ? 'd-none' : ''}" data-inline-preview-layer="image">
                                    <img
                                        src="${escapeHtml(miniature)}"
                                        alt="${escapeHtml(element.title)}"
                                        class="choice-card-thumb"
                                        data-inline-field="imagePreview"
                                    >
                                    <div class="choice-image-preview-placeholder d-none" data-inline-field="imagePreviewPlaceholder">
                                        <span>Apercu element</span>
                                    </div>
                                    <div class="choice-thumb-edit-overlay ${editionImageActive ? '' : 'd-none'}">
                                        <span>Deposer une image ici ou cliquer</span>
                                    </div>
                                </div>
                                <div class="choice-card-preview-layer ${element.mediaType === 'video' && estEnEdition ? '' : 'd-none'}" data-inline-preview-layer="video">
                                    <iframe
                                        class="choice-card-video-preview ${urlVideoEmbedInitialeInline ? '' : 'd-none'}"
                                        src="${escapeHtml(urlVideoEmbedInitialeInline || '')}"
                                        title="Apercu video"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                        allowfullscreen
                                        referrerpolicy="strict-origin-when-cross-origin"
                                        data-inline-field="videoPreviewFrame"
                                    ></iframe>
                                    <div class="choice-video-preview-placeholder ${urlVideoEmbedInitialeInline ? 'd-none' : ''}" data-inline-field="videoPreviewPlaceholder">
                                        <span>Ici sera la previsualisation de la video</span>
                                    </div>
                                </div>
                            </div>
                            <div class="choice-card-content">
                                ${estEnEdition ? blocEdition : blocLecture}
                            </div>
                            <div class="choice-card-actions">
                                ${actions}
                            </div>
                        </article>
                    `;
                }).join('');
            }

            pagination.classList.toggle('d-none', total <= TAILLE_PAGE);
            indicateurPage.textContent = `Page ${etat.pageCourante} / ${Math.max(1, totalPages)}`;
            boutonPagePrecedente.disabled = etat.pageCourante <= 1;
            boutonPageSuivante.disabled = etat.pageCourante >= totalPages;

            const seuilPublicationAtteint = total >= MIN_POUR_PUBLIER;
            const publicationAutorisee = peutPublier();

            zonePublish.classList.toggle('d-none', !seuilPublicationAtteint);
            boutonPublier.disabled = !publicationAutorisee;

            if (hintPublier instanceof HTMLElement) {
                if (!estModeTheme()) {
                    hintPublier.textContent = publicationAutorisee
                        ? 'Le tournoi est pret a etre publie.'
                        : `Le bouton apparait a partir de ${MIN_POUR_PUBLIER} elements.`;
                } else {
                    const { compteA, compteB } = compterParTheme();
                    hintPublier.textContent = publicationAutorisee
                        ? 'Le tournoi est pret a etre publie.'
                        : `En mode theme: minimum ${MIN_POUR_PUBLIER} elements par theme (A: ${compteA}, B: ${compteB}).`;
                }
            }

            synchroniserPayload();

            if (etat.editionInlineId !== null) {
                const carteEdition = Array.from(listeChoices.querySelectorAll('.choice-card'))
                    .find((carte) => carte.getAttribute('data-choice-id') === etat.editionInlineId);

                if (carteEdition instanceof HTMLElement) {
                    mettreAJourFormulaireInlineCarte(carteEdition);
                }
            }
        };

        // Affiche le panneau actif et met a jour l etat des onglets.
        const afficherOnglet = (onglet) => {
            etat.ongletActif = onglet;

            onglets.forEach((element) => {
                const cible = element.getAttribute('data-tab-target');
                element.classList.toggle('is-active', cible === onglet);
            });

            panneaux.forEach((element) => {
                const cible = element.getAttribute('data-tab-panel');
                element.classList.toggle('d-none', cible !== onglet);
            });
        };

        // Active ou desactive l onglet Elements selon les champs obligatoires.
        const rafraichirDisponibiliteOnglets = () => {
            const boutonElements = onglets.find((element) => element.getAttribute('data-tab-target') === 'elements');

            const coverComplet = champsCoverComplets();

            if (boutonElements) {
                boutonElements.disabled = !coverComplet;
            }
        };

        // Ouvre l onglet Elements seulement si la section Tournoi est valide.
        const allerSurElements = () => {
            if (!champsCoverComplets()) {
                afficherErreur('Complète les informations du tournoi avant d\'accéder aux éléments.');
                return;
            }

            cacherErreur();
            afficherOnglet('elements');
        };

        // Met a jour l apercu de cover depuis le champ URL cache.
        const mettreAJourPreviewCover = () => {
            const valeur = champCover.value.trim();
            if (valeur === '') {
                imageCover.classList.add('d-none');
                imageCover.setAttribute('src', '');
                placeholderCover.classList.remove('d-none');
                return;
            }

            imageCover.classList.remove('d-none');
            imageCover.setAttribute('src', valeur);
            placeholderCover.classList.add('d-none');
        };

        // Met a jour l apercu image de l element depuis l URL courante.
        const mettreAJourPreviewImageChoice = () => {
            const valeur = champImageUrl.value.trim();
            if (valeur === '') {
                choiceImagePreviewImage.classList.add('d-none');
                choiceImagePreviewImage.setAttribute('src', '');
                choiceImagePreviewPlaceholder.classList.remove('d-none');
                return;
            }

            choiceImagePreviewImage.classList.remove('d-none');
            choiceImagePreviewImage.setAttribute('src', valeur);
            choiceImagePreviewPlaceholder.classList.add('d-none');
        };

        // Construit une URL embed YouTube a partir du lien fourni.
        const construireUrlYoutubeEmbed = (url) => {
            const source = String(url || '').trim();
            const youtubeId = extraireYoutubeId(source);
            if (!youtubeId) {
                return null;
            }

            return `https://www.youtube.com/embed/${youtubeId}`;
        };

        // Met a jour la preview video: iframe si URL valide, sinon placeholder noir.
        const mettreAJourPreviewVideoChoice = () => {
            const urlEmbed = construireUrlYoutubeEmbed(
                champVideoUrl.value
            );

            if (!urlEmbed) {
                choiceVideoPreviewFrame.classList.add('d-none');
                choiceVideoPreviewFrame.setAttribute('src', '');
                choiceVideoPreviewPlaceholder.classList.remove('d-none');
                return;
            }

            choiceVideoPreviewFrame.classList.remove('d-none');
            choiceVideoPreviewFrame.setAttribute('src', urlEmbed);
            choiceVideoPreviewPlaceholder.classList.add('d-none');
        };

        // Affiche ou masque les controles de theme selon le mode choisi.
        const mettreAJourVisibiliteThemes = () => {
            const modeTheme = estModeTheme();
            ligneThemes.classList.toggle('d-none', !modeTheme);
            blocChoiceTheme.classList.toggle('d-none', !modeTheme);

            if (!modeTheme) {
                champChoiceTheme.value = '';
            }

            mettreAJourOptionsThemeChoice();
        };

        // Propage les noms de themes du tournoi vers les labels d item.
        const mettreAJourOptionsThemeChoice = () => {
            const nomA = champThemeA.value.trim() || 'Thème A';
            const nomB = champThemeB.value.trim() || 'Thème B';

            choiceThemeLabelA.textContent = nomA;
            choiceThemeLabelB.textContent = nomB;
        };

        // Bascule le bloc media actif (image ou video).
        const definirTypeMediaActif = (type) => {
            etat.mediaActif = type;

            cartesTypeMedia.forEach((carte) => {
                carte.classList.toggle('is-active', carte.getAttribute('data-media-choice') === type);
            });

            blocImage.classList.toggle('d-none', type !== 'image');
            blocVideo.classList.toggle('d-none', type !== 'video');

            if (type === 'image') {
                mettreAJourPreviewImageChoice();
            }

            if (type === 'video') {
                mettreAJourPreviewVideoChoice();
            }
        };

        // Extrait un ID YouTube valide depuis les formats d URL supportes.
        const extraireYoutubeId = (url) => {
            if (!url) {
                return null;
            }

            try {
                const parsed = new URL(url);
                const host = parsed.hostname.replace(/^www\./, '');

                if (host === 'youtu.be') {
                    const candidate = parsed.pathname.replace('/', '').trim();
                    return /^[A-Za-z0-9_-]{11}$/.test(candidate) ? candidate : null;
                }

                if (host === 'youtube.com' || host === 'm.youtube.com') {
                    // Gere les formats watch, embed et shorts.
                    const byQuery = parsed.searchParams.get('v');
                    if (byQuery && /^[A-Za-z0-9_-]{11}$/.test(byQuery)) {
                        return byQuery;
                    }

                    if (parsed.pathname.startsWith('/embed/')) {
                        const candidate = parsed.pathname.replace('/embed/', '').split('/')[0].trim();
                        return /^[A-Za-z0-9_-]{11}$/.test(candidate) ? candidate : null;
                    }

                    if (parsed.pathname.startsWith('/shorts/')) {
                        const candidate = parsed.pathname.replace('/shorts/', '').split('/')[0].trim();
                        return /^[A-Za-z0-9_-]{11}$/.test(candidate) ? candidate : null;
                    }
                }
            } catch {
                return null;
            }

            return null;
        };

        // Valide les donnees d un element et construit un payload normalise.
        const validerEtConstruireElement = (options = {}) => {
            const titre = typeof options.title === 'string'
                ? options.title.trim()
                : champChoiceTitle.value.trim();

            if (titre.length === 0) {
                throw new Error('Le nom de l\'élément est obligatoire.');
            }

            const verifierLimite = options.verifierLimite !== false;
            if (verifierLimite && etat.elements.length >= MAX_ELEMENTS) {
                throw new Error('Maximum de 64 éléments atteint.');
            }

            const mediaType = options.mediaType === 'video' || options.mediaType === 'image'
                ? options.mediaType
                : etat.mediaActif;

            const idElement = typeof options.id === 'string' && options.id.trim() !== ''
                ? options.id.trim()
                : null;

            let mediaUrl = '';
            let videoSource = null;
            let startTime = null;
            let endTime = null;

            if (mediaType === 'image') {
                // En mode image, on attend une URL d image televersee.
                const valeurMedia = typeof options.imageUrl === 'string'
                    ? options.imageUrl
                    : (typeof options.mediaUrl === 'string' ? options.mediaUrl : champImageUrl.value);

                mediaUrl = valeurMedia.trim();
                if (mediaUrl === '') {
                    throw new Error('L\'URL de l\'image est obligatoire.');
                }

                if (!mediaUrl.includes('/uploads/')) {
                    try {
                        new URL(mediaUrl);
                    } catch {
                        throw new Error('L\'URL de l\'image est invalide.');
                    }
                }
            } else {
                // En mode video, on valide l URL YouTube et les bornes de temps.
                const valeurVideo = typeof options.videoUrl === 'string'
                    ? options.videoUrl
                    : (typeof options.mediaUrl === 'string' ? options.mediaUrl : champVideoUrl.value);

                videoSource = valeurVideo.trim();
                if (videoSource === '') {
                    throw new Error('L\'URL YouTube est obligatoire pour une vidéo.');
                }

                const urlEmbed = construireUrlYoutubeEmbed(videoSource);
                if (!urlEmbed) {
                    throw new Error('URL YouTube invalide.');
                }

                // Construit une URL embed normalisee pour le rendu.
                mediaUrl = urlEmbed;
            }

            let themeSlot = '';
            if (estModeTheme()) {
                const themeBrut = typeof options.theme === 'string' ? options.theme : champChoiceTheme.value;
                themeSlot = String(themeBrut || '').trim().toUpperCase();
            }

            return {
                id: idElement || `el-${Date.now()}-${Math.floor(Math.random() * 100000)}`,
                title: titre,
                mediaType,
                url: mediaUrl,
                videoSource,
                theme: ['A', 'B'].includes(themeSlot) ? themeSlot : '',
                startTime: null,
                endTime: null,
            };
        };

        // Reinitialise le formulaire item apres ajout ou edition.
        const viderFormulaireChoice = () => {
            champChoiceTitle.value = '';
            champImageUrl.value = '';
            champVideoUrl.value = '';
            champChoiceTheme.value = '';
            afficherUploadStatut(choiceImageUploadStatus, '');
            boutonAjouter.textContent = '+ Ajouter';
            definirTypeMediaActif(etat.mediaActif);
            mettreAJourPreviewImageChoice();
            mettreAJourPreviewVideoChoice();
        };

        // Hydrate depuis le payload JSON initial et normalise les formats.
        const hydraterDepuisPayloadInitial = () => {
            const jsonInitial = racineWizard.dataset.initialElements || '[]';

            try {
                const tableau = JSON.parse(jsonInitial);
                if (!Array.isArray(tableau)) {
                    return;
                }

                // Conserve seulement les objets exploitables et nettoie les champs attendus.
                etat.elements = tableau
                    .filter((item) => item && typeof item === 'object')
                    .map((item, index) => ({
                        id: typeof item.id === 'string' && item.id !== '' ? item.id : `init-${index}`,
                        title: String(item.title || item.titre || '').trim(),
                        mediaType: String(item.mediaType || item.type || '').toLowerCase() === 'video' ? 'video' : 'image',
                        url: String(item.url || '').trim(),
                        videoSource: String(item.videoSource || item.url || '').trim(),
                        theme: ['A', 'B'].includes(String(item.theme || '').toUpperCase()) ? String(item.theme || '').toUpperCase() : '',
                        startTime: Number.isInteger(item.startTime) ? item.startTime : null,
                        endTime: Number.isInteger(item.endTime) ? item.endTime : null,
                    }))
                    .filter((item) => item.title !== '' && item.url !== '');
            } catch {
                etat.elements = [];
            }
        };

        // Bascule le type media du formulaire d'ajout (image/video).
        cartesTypeMedia.forEach((carte) => {
            carte.addEventListener('click', () => {
                const type = carte.getAttribute('data-media-choice');
                if (type === 'image' || type === 'video') {
                    definirTypeMediaActif(type);
                }
            });
        });

        // Gere la navigation entre onglets du wizard.
        onglets.forEach((onglet) => {
            onglet.addEventListener('click', () => {
                const cible = onglet.getAttribute('data-tab-target');

                if (cible === 'tournoi') {
                    cacherErreur();
                    afficherOnglet('tournoi');
                    return;
                }

                if (cible === 'elements') {
                    allerSurElements();
                }
            });
        });

        // Bouton de passage force vers l'etape Elements.
        boutonAllerElements.addEventListener('click', () => {
            allerSurElements();
        });

        // Retour a l'etape Tournoi depuis l'etape Elements.
        boutonRetourTournoi.addEventListener('click', () => {
            cacherErreur();
            afficherOnglet('tournoi');
        });

        // Retour a l'accueil: purge du brouillon local si necessaire.
        if (boutonRetourAccueil) {
            boutonRetourAccueil.addEventListener('click', () => {
                purgerBrouillonLocal();
            });
        }

        // Soumission principale en mode publication.
        boutonPublier.addEventListener('click', () => {
            definirIntentSoumission('publish');
        });

        // Le bouton brouillon redirige vers le profil sans soumettre le wizard.
        if (boutonSauvegarderBrouillon) {
            boutonSauvegarderBrouillon.addEventListener('click', (event) => {
                event.preventDefault();

                if (urlProfilJoueur !== '') {
                    window.location.href = urlProfilJoueur;
                    return;
                }

                window.location.href = '/compte';
            });
        }

        // Nettoie le brouillon avant mise en cache Turbo.
        document.addEventListener('turbo:before-cache', purgerBrouillonLocal);

        // Ouvre le selecteur de fichier pour l'image de couverture.
        coverUploadTrigger.addEventListener('click', () => {
            coverUploadInput.click();
        });

        // Upload de couverture via selection locale.
        coverUploadInput.addEventListener('change', async () => {
            const fichier = coverUploadInput.files && coverUploadInput.files[0] ? coverUploadInput.files[0] : null;
            if (!fichier) {
                return;
            }

            try {
                cacherErreur();
                afficherUploadStatut(coverUploadStatus, 'Upload...');
                const fichierAEnvoyer = await preparerFichierImage(fichier);
                const url = await televerserImage(fichierAEnvoyer);
                champCover.value = url;
                mettreAJourPreviewCover();
                champCover.dispatchEvent(new Event('input', { bubbles: true }));
                afficherUploadStatut(coverUploadStatus, 'Image importee.');
            } catch (erreur) {
                afficherUploadStatut(
                    coverUploadStatus,
                    erreur instanceof Error ? erreur.message : 'Échec de l\'upload.',
                    true
                );
                afficherErreur(erreur instanceof Error ? erreur.message : 'Erreur pendant l\'upload de la couverture.');
            } finally {
                coverUploadInput.value = '';
            }
        });

        // Active l'etat visuel de drop pour la couverture.
        coverUploadBox.addEventListener('dragover', (event) => {
            event.preventDefault();
            coverUploadBox.classList.add('is-dragover');
        });

        // Retire l'etat visuel de drop quand on quitte la zone.
        coverUploadBox.addEventListener('dragleave', () => {
            coverUploadBox.classList.remove('is-dragover');
        });

        // Upload de couverture via glisser-deposer.
        coverUploadBox.addEventListener('drop', async (event) => {
            event.preventDefault();
            coverUploadBox.classList.remove('is-dragover');

            const fichier = event.dataTransfer && event.dataTransfer.files && event.dataTransfer.files[0]
                ? event.dataTransfer.files[0]
                : null;
            if (!fichier) {
                return;
            }

            try {
                cacherErreur();
                afficherUploadStatut(coverUploadStatus, 'Upload...');
                const fichierAEnvoyer = await preparerFichierImage(fichier);
                const url = await televerserImage(fichierAEnvoyer);
                champCover.value = url;
                mettreAJourPreviewCover();
                champCover.dispatchEvent(new Event('input', { bubbles: true }));
                afficherUploadStatut(coverUploadStatus, 'Image importee.');
            } catch (erreur) {
                afficherUploadStatut(
                    coverUploadStatus,
                    erreur instanceof Error ? erreur.message : 'Échec de l\'upload.',
                    true
                );
                afficherErreur(erreur instanceof Error ? erreur.message : 'Erreur pendant l\'upload de la couverture.');
            }
        });

        // Ouvre le selecteur d'image pour un element en creation.
        choiceImageUploadTrigger.addEventListener('click', () => {
            choiceImageUploadInput.click();
        });

        // Upload d'image d'element via selection locale.
        choiceImageUploadInput.addEventListener('change', async () => {
            const fichier = choiceImageUploadInput.files && choiceImageUploadInput.files[0]
                ? choiceImageUploadInput.files[0]
                : null;
            if (!fichier) {
                return;
            }

            try {
                cacherErreur();
                afficherUploadStatut(choiceImageUploadStatus, 'Upload...');
                const fichierAEnvoyer = await preparerFichierImage(fichier);
                const url = await televerserImage(fichierAEnvoyer);
                champImageUrl.value = url;
                mettreAJourPreviewImageChoice();
                afficherUploadStatut(choiceImageUploadStatus, 'Image prete.');
            } catch (erreur) {
                afficherUploadStatut(
                    choiceImageUploadStatus,
                    erreur instanceof Error ? erreur.message : 'Échec de l\'upload.',
                    true
                );
                afficherErreur(erreur instanceof Error ? erreur.message : 'Erreur pendant l\'upload de l\'image.');
            } finally {
                choiceImageUploadInput.value = '';
            }
        });

        // Active l'etat visuel de drop sur la zone image d'element.
        choiceImageUploadBox.addEventListener('dragover', (event) => {
            event.preventDefault();
            choiceImageUploadBox.classList.add('is-dragover');
        });

        // Retire l'etat visuel de drop sur sortie de zone.
        choiceImageUploadBox.addEventListener('dragleave', () => {
            choiceImageUploadBox.classList.remove('is-dragover');
        });

        // Upload d'image d'element via glisser-deposer.
        choiceImageUploadBox.addEventListener('drop', async (event) => {
            event.preventDefault();
            choiceImageUploadBox.classList.remove('is-dragover');

            const fichier = event.dataTransfer && event.dataTransfer.files && event.dataTransfer.files[0]
                ? event.dataTransfer.files[0]
                : null;
            if (!fichier) {
                return;
            }

            try {
                cacherErreur();
                afficherUploadStatut(choiceImageUploadStatus, 'Upload...');
                const fichierAEnvoyer = await preparerFichierImage(fichier);
                const url = await televerserImage(fichierAEnvoyer);
                champImageUrl.value = url;
                mettreAJourPreviewImageChoice();
                afficherUploadStatut(choiceImageUploadStatus, 'Image prete.');
            } catch (erreur) {
                afficherUploadStatut(
                    choiceImageUploadStatus,
                    erreur instanceof Error ? erreur.message : 'Échec de l\'upload.',
                    true
                );
                afficherErreur(erreur instanceof Error ? erreur.message : 'Erreur pendant l\'upload de l\'image.');
            }
        });

        // Maintient la preview video de l'etape creation synchronisee avec le champ URL.
        const gererMajPreviewVideo = () => {
            if (etat.mediaActif === 'video') {
                mettreAJourPreviewVideoChoice();
            }
        };

        champVideoUrl.addEventListener('input', gererMajPreviewVideo);
        champVideoUrl.addEventListener('change', gererMajPreviewVideo);

        // Ajoute un nouvel element (ou met a jour selon l'id) puis rerend la liste.
        boutonAjouter.addEventListener('click', async () => {
            if (operationElementEnCours) {
                return;
            }

            try {
                cacherErreur();

                const elementLocal = validerEtConstruireElement();

                operationElementEnCours = true;
                boutonAjouter.disabled = true;

                const elementSauvegarde = await sauvegarderElementEnBase(elementLocal);
                const indexExistant = etat.elements.findIndex((item) => item.id === elementLocal.id);

                if (indexExistant >= 0) {
                    etat.elements[indexExistant] = elementSauvegarde;
                } else {
                    etat.elements.push(elementSauvegarde);
                }

                etat.editionInlineId = null;
                etat.editionImageInlineId = null;
                etat.pageCourante = 1;
                viderFormulaireChoice();
                rafraichirDisponibiliteOnglets();
                renderListe();
            } catch (erreur) {
                afficherErreur(erreur instanceof Error ? erreur.message : 'Erreur pendant la sauvegarde de l\'élément.');
            } finally {
                operationElementEnCours = false;
                boutonAjouter.disabled = false;
            }
        });

        // Routeur d'actions inline sur une carte (editer, sauver, supprimer, etc.).
        listeChoices.addEventListener('click', async (event) => {
            const cible = event.target;
            if (!(cible instanceof HTMLElement)) {
                return;
            }

            const actionElement = cible.closest('[data-action][data-choice-id]');
            if (!(actionElement instanceof HTMLElement) || !listeChoices.contains(actionElement)) {
                return;
            }

            const action = actionElement.getAttribute('data-action');
            const idElement = actionElement.getAttribute('data-choice-id');
            if (!action || !idElement) {
                return;
            }

            if (action === 'inline-image-trigger') {
                etat.editionImageInlineId = idElement;
                renderListe();
                ouvrirSelecteurImageInlineCarte(idElement);

                return;
            }

            if (action === 'edit') {
                cacherErreur();
                const elementAEditer = etat.elements.find((item) => item.id === idElement);
                etat.editionInlineId = idElement;
                etat.editionImageInlineId = elementAEditer && elementAEditer.mediaType === 'image'
                    ? idElement
                    : null;
                renderListe();

                return;
            }

            if (action === 'cancel-inline') {
                cacherErreur();
                if (etat.editionInlineId === idElement) {
                    etat.editionInlineId = null;
                    etat.editionImageInlineId = null;
                    renderListe();
                }

                return;
            }

            if (action === 'save-inline') {
                if (operationElementEnCours) {
                    return;
                }

                const carte = actionElement.closest('.choice-card');
                if (!(carte instanceof HTMLElement)) {
                    return;
                }

                const lireChamp = (nom) => carte.querySelector(`[data-inline-field="${nom}"]`);
                const champTitreInline = lireChamp('title');
                const champThemeInline = carte.querySelector('[data-inline-field="themeRadio"]:checked');

                const elementExistant = etat.elements.find((item) => item.id === idElement);
                if (!elementExistant) {
                    return;
                }

                if (!(champTitreInline instanceof HTMLInputElement)) {
                    return;
                }

                const mediaTypeActif = obtenirTypeMediaInlineCarte(carte, elementExistant.mediaType);
                const blocMediaActif = carte.querySelector(`[data-inline-media-block="${mediaTypeActif}"]`);
                if (!(blocMediaActif instanceof HTMLElement)) {
                    return;
                }

                const lireChampDansBloc = (nom) => blocMediaActif.querySelector(`[data-inline-field="${nom}"]`);
                const champMediaInline = lireChampDansBloc('mediaUrl');
                if (!(champMediaInline instanceof HTMLInputElement)) {
                    return;
                }

                try {
                    cacherErreur();
                    operationElementEnCours = true;

                    const elementMisAJour = validerEtConstruireElement({
                        id: idElement,
                        title: champTitreInline.value,
                        mediaType: mediaTypeActif,
                        mediaUrl: champMediaInline.value,
                        theme: champThemeInline instanceof HTMLInputElement ? champThemeInline.value : '',
                        verifierLimite: false,
                    });

                    const elementSauvegarde = await sauvegarderElementEnBase(elementMisAJour);
                    const indexExistant = etat.elements.findIndex((item) => item.id === idElement);

                    if (indexExistant < 0) {
                        throw new Error('Element introuvable. Recharge la page puis recommence.');
                    }

                    etat.elements[indexExistant] = elementSauvegarde;
                    etat.editionInlineId = null;
                    etat.editionImageInlineId = null;

                    rafraichirDisponibiliteOnglets();
                    renderListe();
                } catch (erreur) {
                    afficherErreur(erreur instanceof Error ? erreur.message : 'Erreur pendant la mise a jour de l\'element.');
                } finally {
                    operationElementEnCours = false;
                }

                return;
            }

            if (action === 'delete') {
                if (operationElementEnCours) {
                    return;
                }

                try {
                    cacherErreur();
                    operationElementEnCours = true;
                    await supprimerElementEnBase(idElement);

                    etat.elements = etat.elements.filter((item) => item.id !== idElement);
                    if (etat.editionInlineId === idElement) {
                        etat.editionInlineId = null;
                    }
                    if (etat.editionImageInlineId === idElement) {
                        etat.editionImageInlineId = null;
                    }

                    rafraichirDisponibiliteOnglets();
                    renderListe();
                } catch (erreur) {
                    afficherErreur(erreur instanceof Error ? erreur.message : 'Erreur pendant la suppression de l\'élément.');
                } finally {
                    operationElementEnCours = false;
                }

                return;
            }
        });

        // Gere les changements de champs inline (type media, URL, upload image locale).
        listeChoices.addEventListener('change', async (event) => {
            const cible = event.target;
            if (!(cible instanceof HTMLElement)) {
                return;
            }

            const nomChampInline = cible.getAttribute('data-inline-field');
            if (nomChampInline === 'mediaTypeRadio') {
                if (!(cible instanceof HTMLInputElement) || !cible.checked) {
                    return;
                }

                const carteInline = cible.closest('.choice-card');
                if (!(carteInline instanceof HTMLElement)) {
                    return;
                }

                mettreAJourFormulaireInlineCarte(carteInline);

                const idCarte = carteInline.getAttribute('data-choice-id') || '';
                const typeActif = obtenirTypeMediaInlineCarte(carteInline, 'image');
                const boutonImage = carteInline.querySelector('[data-action="inline-image-trigger"]');
                if (boutonImage instanceof HTMLElement) {
                    boutonImage.classList.toggle('d-none', typeActif !== 'image');
                }

                if (typeActif === 'image' && etat.editionInlineId === idCarte) {
                    etat.editionImageInlineId = idCarte;
                } else if (etat.editionImageInlineId === idCarte) {
                    etat.editionImageInlineId = null;
                }

                const zoneMiniature = carteInline.querySelector('[data-inline-thumb-zone]');
                if (zoneMiniature instanceof HTMLElement) {
                    const editionImageActive = typeActif === 'image' && etat.editionImageInlineId === idCarte;
                    zoneMiniature.classList.toggle('is-inline-image-drop-enabled', editionImageActive);
                }

                return;
            }

            if (nomChampInline === 'mediaUrl' || nomChampInline === 'startTime' || nomChampInline === 'endTime') {
                const carteInline = cible.closest('.choice-card');
                if (!(carteInline instanceof HTMLElement)) {
                    return;
                }

                if (obtenirTypeMediaInlineCarte(carteInline, 'image') === 'video') {
                    mettreAJourPreviewVideoInlineCarte(carteInline);
                }

                return;
            }

            if (nomChampInline !== 'imageFile') {
                return;
            }

            if (!(cible instanceof HTMLInputElement)) {
                return;
            }

            const carte = cible.closest('.choice-card');
            if (!(carte instanceof HTMLElement)) {
                return;
            }

            const fichier = cible.files && cible.files[0] ? cible.files[0] : null;
            if (!fichier) {
                return;
            }

            await televerserImageInlineCarte(carte, fichier);
            cible.value = '';
        });

        // Clic sur la miniature inline: ouvre le selecteur fichier si mode image actif.
        listeChoices.addEventListener('click', (event) => {
            const cible = event.target;
            if (!(cible instanceof HTMLElement)) {
                return;
            }

            const zoneMiniature = cible.closest('[data-inline-thumb-zone]');
            if (!(zoneMiniature instanceof HTMLElement) || !listeChoices.contains(zoneMiniature)) {
                return;
            }

            const idElement = zoneMiniature.getAttribute('data-choice-id');
            if (!idElement || etat.editionImageInlineId !== idElement) {
                return;
            }

            ouvrirSelecteurImageInlineCarte(idElement);
        });

        // Signale visuellement qu'une miniature inline accepte un drop.
        listeChoices.addEventListener('dragenter', (event) => {
            const cible = event.target;
            if (!(cible instanceof HTMLElement)) {
                return;
            }

            const zoneMiniature = cible.closest('[data-inline-thumb-zone]');
            if (!(zoneMiniature instanceof HTMLElement) || !listeChoices.contains(zoneMiniature)) {
                return;
            }

            const idElement = zoneMiniature.getAttribute('data-choice-id');
            if (!idElement || etat.editionImageInlineId !== idElement) {
                return;
            }

            event.preventDefault();
            zoneMiniature.classList.add('is-dragover');
        });

        // Autorise le drop de fichier sur une miniature inline active.
        listeChoices.addEventListener('dragover', (event) => {
            const cible = event.target;
            if (!(cible instanceof HTMLElement)) {
                return;
            }

            const zoneMiniature = cible.closest('[data-inline-thumb-zone]');
            if (!(zoneMiniature instanceof HTMLElement) || !listeChoices.contains(zoneMiniature)) {
                return;
            }

            const idElement = zoneMiniature.getAttribute('data-choice-id');
            if (!idElement || etat.editionImageInlineId !== idElement) {
                return;
            }

            event.preventDefault();
            zoneMiniature.classList.add('is-dragover');
        });

        // Nettoie l'etat visuel de drop quand la souris sort de la miniature.
        listeChoices.addEventListener('dragleave', (event) => {
            const cible = event.target;
            if (!(cible instanceof HTMLElement)) {
                return;
            }

            const zoneMiniature = cible.closest('[data-inline-thumb-zone]');
            if (!(zoneMiniature instanceof HTMLElement) || !listeChoices.contains(zoneMiniature)) {
                return;
            }

            zoneMiniature.classList.remove('is-dragover');
        });

        // Depose une image sur la miniature inline et lance l'upload associe.
        listeChoices.addEventListener('drop', async (event) => {
            const cible = event.target;
            if (!(cible instanceof HTMLElement)) {
                return;
            }

            const zoneMiniature = cible.closest('[data-inline-thumb-zone]');
            if (!(zoneMiniature instanceof HTMLElement) || !listeChoices.contains(zoneMiniature)) {
                return;
            }

            const idElement = zoneMiniature.getAttribute('data-choice-id');
            if (!idElement || etat.editionImageInlineId !== idElement) {
                return;
            }

            event.preventDefault();
            zoneMiniature.classList.remove('is-dragover');

            const fichier = event.dataTransfer && event.dataTransfer.files && event.dataTransfer.files[0]
                ? event.dataTransfer.files[0]
                : null;
            if (!fichier) {
                return;
            }

            const carte = zoneMiniature.closest('.choice-card');
            if (!(carte instanceof HTMLElement)) {
                return;
            }

            await televerserImageInlineCarte(carte, fichier);
        });

        // Pagination de la liste d'elements.
        boutonPagePrecedente.addEventListener('click', () => {
            etat.pageCourante = Math.max(1, etat.pageCourante - 1);
            renderListe();
        });

        boutonPageSuivante.addEventListener('click', () => {
            etat.pageCourante += 1;
            renderListe();
        });

        // Reagit aux modifications des infos tournoi qui impactent validations et rendu.
        [champNom, champDescription, champCover, champMode, champThemeA, champThemeB].forEach((champ) => {
            champ.addEventListener('input', () => {
                mettreAJourPreviewCover();
                mettreAJourVisibiliteThemes();
                rafraichirDisponibiliteOnglets();
                renderListe();
            });

            champ.addEventListener('change', () => {
                mettreAJourPreviewCover();
                mettreAJourVisibiliteThemes();
                rafraichirDisponibiliteOnglets();
                renderListe();
            });
        });

        // Valide la soumission finale (brouillon ou publication) avant envoi serveur.
        formulaire.addEventListener('submit', (event) => {
            cacherErreur();

            const estSoumissionBrouillon = champIntentSoumission.value === 'draft';

            if (estSoumissionBrouillon) {
                soumissionValidee = true;
                synchroniserPayload();
                return;
            }

            if (!champsCoverComplets()) {
                event.preventDefault();
                afficherErreur('Les informations du tournoi sont incomplètes ou invalides.');
                afficherOnglet('tournoi');
                return;
            }

            if (!peutPublier()) {
                event.preventDefault();

                if (estModeTheme()) {
                    const { compteA, compteB } = compterParTheme();
                    afficherErreur(`Publication impossible : minimum 8 éléments par thème (A : ${compteA}, B : ${compteB}).`);
                } else {
                    afficherErreur('Publication impossible : minimum 8 éléments.');
                }

                afficherOnglet('elements');
                return;
            }

            soumissionValidee = true;
            synchroniserPayload();
        });

        // Sequence d'initialisation de l'interface au chargement.
        hydraterDepuisPayloadInitial();
        desactiverRetentionNavigateur();
        mettreAJourPreviewCover();
        mettreAJourPreviewImageChoice();
        mettreAJourPreviewVideoChoice();
        mettreAJourVisibiliteThemes();
        definirTypeMediaActif('image');
        rafraichirDisponibiliteOnglets();
        renderListe();
        afficherOnglet('tournoi');
    }
};

initialiserWizardTournoi();
document.addEventListener('turbo:load', initialiserWizardTournoi);
