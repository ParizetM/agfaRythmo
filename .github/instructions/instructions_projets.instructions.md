---
applyTo: '**'
---
# Instructions pour GitHub Copilot
ne pas utiliser de navigateur simple dans vscode
Ne pas faire npm run dev
## Contexte du projet

Ce projet vise à recréer l’application Capella en version web, avec une architecture backend Laravel (API REST) et un frontend Vue.js. L’application permet l’import de vidéos, l’édition de texte synchronisé (bande rythmo), la prévisualisation et la sauvegarde des projets.

## Fonctionnalités v1 (MVP)

- Import de vidéos multi-formats
- Interface d’édition texte + timecodes (format simple) et indicateur de changement de plan
- Affichage de la bande rythmo synchronisée
- Preview vidéo + texte intégré
- Sauvegarde/chargement des projets (JSON stocké en base ou fichier)

## Stack technique

- **Backend** : Laravel (API REST pour projets, vidéos, textes)
- **Base de données** : SQLite
- **Stockage** : vidéos
- **Frontend** : Vue.js
    - Player vidéo HTML5
    - tailwindcss pour le style
    - Vue Router pour la navigation
    - Interface d’édition et preview
    - Bande rythmo animée 
- **Desktop** : Electron (intègre le frontend Vue, accès fichiers locaux pour mode offline)

## Bonnes pratiques attendues

- Respecter la séparation frontend/backend (API REST)
- Utiliser des conventions Laravel et Vue.js standards avec tailwindcss
- Privilégier la clarté et la maintenabilité du code
- Documenter les endpoints API et les composants principaux
- Prévoir des hooks/tests unitaires pour les fonctions critiques

## Cibles

- VSCode avec GitHub Copilot activé
- Développement cross-platform (web et desktop via Electron)

## Style de l'application

Couleurs : 
Fond #121827
couleur menu #202937
couleur forte #8455F6
couleur bouton #384152
Texte #FFFFFF
