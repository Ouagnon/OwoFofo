/**
 * Gère tout le glisser-déposer de l'inter-manche.
 *
 * Objectif:
 * - classement des perdants,
 * - échanges perdant/vainqueur,
 * - placement de l'élément impair.
 */
const initialiserGlisserDeposerIntermanche = () => {
    const form = document.getElementById('intermanche-form');
    if (!form) {
        return;
    }

    if (form.dataset.intermancheInit === '1') {
        return;
    }
    form.dataset.intermancheInit = '1';

    const losersList = document.getElementById('intermanche-losers');
    const winnersList = document.getElementById('intermanche-winners');
    const classementInput = document.getElementById('intermanche-classement-json');
    const repechageInput = document.getElementById('intermanche-repechage-json');
    const impairInput = document.getElementById('intermanche-impair-json');
    const oddZone = document.getElementById('intermanche-odd-zone');
    const feedback = document.getElementById('intermanche-feedback');

    if (!losersList || !winnersList || !classementInput || !repechageInput) {
        return;
    }

    const modeTheme = form.dataset.modeTheme === '1';
    const repechageEnabled = form.dataset.repechageEnabled === '1';
    const rankingEnabled = losersList.dataset.rankingEnabled === '1';
    const rankMax = Number(losersList.dataset.rankMax || '1');
    const hasOddElement = oddZone instanceof HTMLElement;
    let draggedCard = null;

    // Affiche un message de feedback utilisateur sous la grille inter-manche.
    const showFeedback = (message, isError = false) => {
        if (!feedback) {
            return;
        }

        feedback.textContent = message;
        feedback.classList.toggle('is-error', isError);
        feedback.classList.toggle('is-visible', message !== '');
    };

    // Helpers de récupération des éléments du DOM inter-manche.
    const getLoserSlots = () => Array.from(losersList.querySelectorAll('.intermanche-loss-slot'));
    const getLoserCards = () => getLoserSlots()
        .map((slot) => slot.querySelector('.intermanche-loser'))
        .filter((card) => card instanceof HTMLElement);
    const getWinnerSlots = () => Array.from(winnersList.querySelectorAll('.intermanche-slot'));
    const getImpairCard = () => form.querySelector('.intermanche-tile[data-is-impair="1"]');

    const createLoserSlot = (card) => {
        const slot = document.createElement('li');
        slot.className = 'intermanche-slot intermanche-loss-slot';
        slot.dataset.lossSlot = '1';

        const label = document.createElement('div');
        label.className = 'intermanche-slot-label';

        const labelText = document.createElement('span');
        labelText.className = 'intermanche-rank-label';
        labelText.textContent = rankingEnabled ? 'Position finale' : 'Perdant';

        label.appendChild(labelText);
        if (rankingEnabled) {
            const labelValue = document.createElement('span');
            labelValue.className = 'intermanche-rank-value';
            labelValue.textContent = '#0';
            label.appendChild(labelValue);
        }
        slot.appendChild(label);
        slot.appendChild(card);

        return slot;
    };

    const insertLoserSlotByPointer = (slot, event) => {
        const targetSlot = event.target.closest('.intermanche-loss-slot');
        if (!(targetSlot instanceof HTMLElement)) {
            losersList.appendChild(slot);
            return;
        }

        const targetRect = targetSlot.getBoundingClientRect();
        const before = event.clientY < targetRect.top + (targetRect.height / 2);
        losersList.insertBefore(slot, before ? targetSlot : targetSlot.nextSibling);
    };

    const refreshLoserRankLabels = () => {
        const slots = getLoserSlots();
        const rankStart = Math.max(1, rankMax - slots.length + 1);

        slots.forEach((slot, index) => {
            const value = slot.querySelector('.intermanche-rank-value');
            if (value instanceof HTMLElement) {
                value.textContent = `#${rankStart + index}`;
            }
        });
    };

    // Synchronise les payloads JSON envoyés au backend Symfony.
    const syncPayload = () => {
        const classement = getLoserCards().map((card) => Number(card.dataset.elementId || '0')).filter((id) => id > 0);
        classementInput.value = JSON.stringify(classement);

        const swaps = [];
        getWinnerSlots().forEach((slot) => {
            const baseWinnerId = Number(slot.dataset.slotWinnerId || '0');
            const current = slot.querySelector('.intermanche-tile');
            if (!(current instanceof HTMLElement) || baseWinnerId < 1) {
                return;
            }

            const isImpair = current.dataset.isImpair === '1';
            const currentId = Number(current.dataset.elementId || '0');
            if (repechageEnabled && !isImpair && currentId > 0 && currentId !== baseWinnerId) {
                swaps.push({ loserId: currentId, winnerId: baseWinnerId });
            }
        });

        repechageInput.value = JSON.stringify(swaps);

        if (impairInput instanceof HTMLInputElement && hasOddElement) {
            const oddCard = getImpairCard();
            let impairPayload = { position: 'middle' };

            if (oddCard instanceof HTMLElement) {
                const oddWinnerSlot = oddCard.closest('.intermanche-slot[data-slot-winner-id]');
                const oddLoserSlot = oddCard.closest('.intermanche-loss-slot');

                if (oddWinnerSlot instanceof HTMLElement) {
                    impairPayload = {
                        position: 'winner',
                        winnerId: Number(oddWinnerSlot.dataset.slotWinnerId || '0'),
                    };
                } else if (oddLoserSlot instanceof HTMLElement) {
                    impairPayload = { position: 'losers' };
                }
            }

            impairInput.value = JSON.stringify(impairPayload);
        }
    };

    // Lie les événements drag & drop d'une carte donnée.
    const bindDraggableCard = (card) => {
        if (!(card instanceof HTMLElement) || card.dataset.dragBound === '1' || !card.draggable) {
            return;
        }

        card.dataset.dragBound = '1';

        card.addEventListener('dragstart', (event) => {
            draggedCard = card;
            card.classList.add('is-dragging');
            if (event.dataTransfer) {
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', card.dataset.elementId || '');
            }
            showFeedback('');
        });

        card.addEventListener('dragend', () => {
            card.classList.remove('is-dragging');
            draggedCard = null;
            syncPayload();
        });
    };

    getLoserCards().forEach((card) => bindDraggableCard(card));
    if (hasOddElement) {
        bindDraggableCard(getImpairCard());
        oddZone.classList.toggle('is-empty', !(oddZone.querySelector('.intermanche-tile') instanceof HTMLElement));
    }

    losersList.addEventListener('dragover', (event) => {
        if (!draggedCard) {
            return;
        }

        const draggedIsImpair = draggedCard.dataset.isImpair === '1';
        if (draggedIsImpair && !losersList.contains(draggedCard)) {
            event.preventDefault();
            if (event.dataTransfer) {
                event.dataTransfer.dropEffect = 'move';
            }
            return;
        }

        if (!rankingEnabled || !losersList.contains(draggedCard)) {
            return;
        }

        event.preventDefault();
        if (event.dataTransfer) {
            event.dataTransfer.dropEffect = 'move';
        }

        const draggedSlot = draggedCard.closest('.intermanche-loss-slot');
        if (!(draggedSlot instanceof HTMLElement)) {
            return;
        }

        const targetSlot = event.target.closest('.intermanche-loss-slot');
        if (!(targetSlot instanceof HTMLElement) || targetSlot === draggedSlot) {
            return;
        }

        const targetRect = targetSlot.getBoundingClientRect();
        const before = event.clientY < targetRect.top + (targetRect.height / 2);
        losersList.insertBefore(draggedSlot, before ? targetSlot : targetSlot.nextSibling);
        refreshLoserRankLabels();
    });

    losersList.addEventListener('drop', (event) => {
        if (!draggedCard) {
            return;
        }

        const draggedIsImpair = draggedCard.dataset.isImpair === '1';
        if (!draggedIsImpair) {
            return;
        }

        event.preventDefault();
        if (event.dataTransfer) {
            event.dataTransfer.dropEffect = 'move';
        }

        if (losersList.contains(draggedCard)) {
            refreshLoserRankLabels();
            syncPayload();
            return;
        }

        draggedCard.classList.remove('intermanche-odd', 'intermanche-winner', 'is-promoted', 'is-dragging');
        draggedCard.classList.add('intermanche-loser', 'is-demoted');
        draggedCard.dataset.canPromote = '1';
        draggedCard.draggable = rankingEnabled || repechageEnabled;
        draggedCard.dataset.dragBound = '0';

        const newSlot = createLoserSlot(draggedCard);
        insertLoserSlotByPointer(newSlot, event);
        bindDraggableCard(draggedCard);

        if (hasOddElement) {
            oddZone.classList.toggle('is-empty', !(oddZone.querySelector('.intermanche-tile') instanceof HTMLElement));
        }

        draggedCard = null;
        showFeedback('');
        refreshLoserRankLabels();
        syncPayload();
    });

    getWinnerSlots().forEach((slot) => {
        slot.addEventListener('dragover', (event) => {
            if (!draggedCard) {
                return;
            }

            const draggedIsImpair = draggedCard.dataset.isImpair === '1';
            if (!draggedIsImpair && !repechageEnabled) {
                return;
            }

            event.preventDefault();
            slot.classList.add('is-drop-target');
        });

        slot.addEventListener('dragleave', () => {
            slot.classList.remove('is-drop-target');
        });

        slot.addEventListener('drop', (event) => {
            slot.classList.remove('is-drop-target');

            if (!draggedCard) {
                return;
            }

            const draggedIsImpair = draggedCard.dataset.isImpair === '1';
            if (!draggedIsImpair && !repechageEnabled) {
                return;
            }

            event.preventDefault();
            if (event.dataTransfer) {
                event.dataTransfer.dropEffect = 'move';
            }

            if (!draggedIsImpair && draggedCard.dataset.canPromote !== '1') {
                showFeedback('Seuls les perdants peuvent être promus.', true);
                return;
            }

            const sourceSlot = draggedCard.closest('.intermanche-loss-slot');
            if (!draggedIsImpair && !(sourceSlot instanceof HTMLElement)) {
                showFeedback('Impossible de déterminer la position du perdant.', true);
                return;
            }

            const draggedThemeId = Number(draggedCard.dataset.themeId || '0');
            const slotThemeId = Number(slot.dataset.themeId || '0');
            if (modeTheme && draggedThemeId !== slotThemeId) {
                showFeedback('Mode VS thème : échange possible uniquement avec un élément du même thème.', true);
                return;
            }

            const currentWinner = slot.querySelector('.intermanche-tile');
            if (!(currentWinner instanceof HTMLElement)) {
                return;
            }

            const promoted = draggedCard;
            const demoted = currentWinner;

            promoted.classList.remove('intermanche-odd', 'intermanche-loser', 'is-demoted', 'is-dragging');
            promoted.classList.add('intermanche-winner', 'is-promoted');
            promoted.dataset.canPromote = '0';
            promoted.draggable = false;
            promoted.dataset.dragBound = '0';

            demoted.classList.remove('intermanche-winner', 'is-promoted');
            demoted.classList.add('intermanche-loser', 'is-demoted');
            demoted.dataset.canPromote = '1';
            demoted.draggable = rankingEnabled || repechageEnabled;
            demoted.dataset.dragBound = '0';

            if (sourceSlot instanceof HTMLElement) {
                sourceSlot.replaceChild(demoted, promoted);
                slot.appendChild(promoted);
            } else {
                slot.replaceChild(promoted, currentWinner);
                const demotedSlot = createLoserSlot(demoted);
                losersList.appendChild(demotedSlot);

                if (hasOddElement) {
                    oddZone.classList.toggle('is-empty', !(oddZone.querySelector('.intermanche-tile') instanceof HTMLElement));
                }
            }

            bindDraggableCard(demoted);
            draggedCard = null;
            showFeedback('');
            refreshLoserRankLabels();
            syncPayload();
        });
    });

    // Contrôle final avant soumission (cas de l'élément impair non placé).
    form.addEventListener('submit', (event) => {
        syncPayload();

        if (!(impairInput instanceof HTMLInputElement) || !hasOddElement) {
            return;
        }

        let impairPayload = { position: 'middle' };
        try {
            const parsed = JSON.parse(impairInput.value || '{}');
            if (parsed && typeof parsed === 'object') {
                impairPayload = parsed;
            }
        } catch (error) {
            impairPayload = { position: 'middle' };
        }

        if (impairPayload.position === 'middle') {
            event.preventDefault();
            showFeedback('Place l\'élément impair : dans les perdants ou en remplacement d\'un gagnant.', true);
        }
    });

    refreshLoserRankLabels();
    syncPayload();
};

/**
 * Anime l'expérience de vote d'un duel.
 *
 * Effets:
 * - hover croisé des deux cartes,
 * - verrouillage au clic,
 * - agrandissement du gagnant et disparition du perdant.
 */
const initialiserAnimationsVoteDuel = () => {
    const arena = document.querySelector('.duel-arena');
    if (!(arena instanceof HTMLElement) || arena.dataset.voteInit === '1') {
        return;
    }

    const cards = Array.from(arena.querySelectorAll('[data-vote-card="1"]'));
    const forms = Array.from(arena.querySelectorAll('[data-vote-form="1"]'));
    if (cards.length !== 2 || forms.length !== 2) {
        return;
    }

    arena.dataset.voteInit = '1';

    const setHoverState = (index) => {
        if (arena.classList.contains('is-vote-locked')) {
            return;
        }

        arena.classList.toggle('is-hover-left', index === 0);
        arena.classList.toggle('is-hover-right', index === 1);
    };

    const clearHoverState = () => {
        if (arena.classList.contains('is-vote-locked')) {
            return;
        }

        arena.classList.remove('is-hover-left', 'is-hover-right');
    };

    cards.forEach((card, index) => {
        card.addEventListener('mouseenter', () => setHoverState(index));
        card.addEventListener('mouseleave', clearHoverState);
    });

    forms.forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (arena.classList.contains('is-vote-locked')) {
                return;
            }

            event.preventDefault();

            const winnerCard = form.closest('[data-vote-card="1"]');
            if (!(winnerCard instanceof HTMLElement)) {
                form.submit();
                return;
            }

            const loserCard = cards.find((card) => card !== winnerCard);

            arena.classList.remove('is-hover-left', 'is-hover-right');
            arena.classList.add('is-vote-locked');
            winnerCard.classList.add('is-vote-winner');
            if (loserCard instanceof HTMLElement) {
                loserCard.classList.add('is-vote-loser');
            }

            window.setTimeout(() => {
                form.submit();
            }, 460);
        });
    });
};

initialiserGlisserDeposerIntermanche();
initialiserAnimationsVoteDuel();
document.addEventListener('turbo:load', () => {
    initialiserGlisserDeposerIntermanche();
    initialiserAnimationsVoteDuel();
});
