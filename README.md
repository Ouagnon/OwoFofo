# OwoFofo

Modele de donnees Symfony/Doctrine pour un tournoi de duels media (image/video par URL)
avec mode theme vs theme, gestion des impairs, repechage et classement des perdants.

## Entites principales

- `Theme` : categorie reutilisable (ex: fruits, legumes), avec `nom` et `slug` uniques.
- `Tournoi` : configuration du tournoi (themes A/B, options de regles, taille cible).
- `Element` : candidat du tournoi (`titre`, `mediaUrl`, `mediaType`, `theme`, `seed`).
- `Joueur` : utilisateur qui joue une partie sur un tournoi.
- `Partie` : session de jeu d'un joueur sur un tournoi, avec vainqueur final et timestamps.
- `Manche` : round d'une partie (64, 32, 16, 8, 4, 2), strategie d'appariement.
- `Duel` : affrontement principal entre deux elements, avec vainqueur et type de confrontation.

## Fonctionnalites avancees

- `DecisionImpair` : stocke la decision de fin de manche quand un element est impair
	(remplacer ou eliminer).
- `Repechage` : permet de rejouer un perdant contre un vainqueur du meme type/theme.
- `ClassementPerdant` : classement ordonne des perdants d'une manche.

## Enums metier

- `EtatPartie`, `EtatManche`, `EtatDuel`
- `TypeManche`, `TypeMedia`
- `ModeTournoi`, `ModeAppariement`
- `ActionImpair`, `TypeConfrontation`

## Migration

La migration `Version20260409120000` cree un schema propre aligne sur ce modele.
Attention: elle est destructive (drop/recreate des tables metier) et convient a une base de dev
ou a une phase de reset du projet.