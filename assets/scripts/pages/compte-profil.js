/**
 * Lie les confirmations de suppression sur la page profil.
 */
const initialiserPageProfilCompte = () => {
    const formulairesSuppression = document.querySelectorAll('form.js-confirm-delete-partie');
    if (formulairesSuppression.length === 0) {
        return;
    }

    formulairesSuppression.forEach((formulaire) => {
        // Empêche d'ajouter plusieurs listeners au même formulaire.
        if (!(formulaire instanceof HTMLFormElement) || formulaire.dataset.confirmBound === '1') {
            return;
        }

        formulaire.dataset.confirmBound = '1';
        formulaire.addEventListener('submit', (event) => {
            const message = formulaire.dataset.confirmMessage || 'Confirmer cette suppression ?';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });
};

initialiserPageProfilCompte();
document.addEventListener('turbo:load', initialiserPageProfilCompte);
