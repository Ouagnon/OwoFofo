/**
 * Initialise les interactions de la page de configuration d'une partie.
 */
const initialiserPageConfigurationPartie = () => {
    const conteneurPage = document.querySelector('.partie-config-shell');
    if (!(conteneurPage instanceof HTMLElement)) {
        return;
    }

    // Empêche une initialisation multiple après navigation Turbo.
    if (conteneurPage.dataset.pageInit === '1') {
        return;
    }
    conteneurPage.dataset.pageInit = '1';

    // Gestion de l'option "utiliser tous" pour masquer/afficher la grille de tailles.
    const interrupteurUtiliserTous = document.getElementById('utiliser_tous');
    const grilleTailles = document.getElementById('partie-size-grid');

    if (interrupteurUtiliserTous && grilleTailles) {
        const rafraichirAffichageTailles = () => {
            grilleTailles.classList.toggle('is-hidden', interrupteurUtiliserTous.checked);
        };

        interrupteurUtiliserTous.addEventListener('change', rafraichirAffichageTailles);
        rafraichirAffichageTailles();
    }

    // Confirmation de suppression/redémarrage d'une partie déjà en cours.
    const formulairesRecommencer = conteneurPage.querySelectorAll('form.js-confirm-restart-partie');
    formulairesRecommencer.forEach((formulaire) => {
        if (!(formulaire instanceof HTMLFormElement) || formulaire.dataset.confirmBound === '1') {
            return;
        }

        formulaire.dataset.confirmBound = '1';
        formulaire.addEventListener('submit', (event) => {
            const message = formulaire.dataset.confirmMessage || 'Confirmer cette action ?';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });
};

initialiserPageConfigurationPartie();
document.addEventListener('turbo:load', initialiserPageConfigurationPartie);
