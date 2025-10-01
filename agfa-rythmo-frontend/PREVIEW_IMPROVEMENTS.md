# Amélioration de l'aperçu des paramètres

## 🎨 Changements apportés

### Problème initial
- L'aperçu n'était pas représentatif de la réalité
- Difficile de comprendre où se trouve la vidéo par rapport à la bande rythmo
- Visualisation peu claire de la différence entre les modes "par-dessus" et "sous la vidéo"

### Solution implémentée

#### 1. **Nouvelle zone vidéo simulée**
- Vidéo avec un dégradé de couleurs moderne (bleu/violet)
- Grille en arrière-plan pour simuler une image vidéo
- Icône play au centre pour indiquer qu'il s'agit d'une vidéo
- Label "ZONE VIDÉO" dans le coin supérieur gauche
- Bordure visible pour délimiter clairement la zone

#### 2. **Aperçu réaliste des deux modes**

##### Mode "Sous la vidéo" (under)
```
┌─────────────────┐
│   ZONE VIDÉO    │  ← Vidéo en haut
│   (simulée)     │
└─────────────────┘
┌─────────────────┐
│  Bande Rythmo   │  ← Bande collée en dessous
└─────────────────┘
```

##### Mode "Par-dessus" (over)
```
┌─────────────────┐
│   ZONE VIDÉO    │
│   (simulée)     │
│ ┌─────────────┐ │  ← Bande superposée
│ │ Bande Rythmo│ │     ancrée en bas
│ └─────────────┘ │
└─────────────────┘
```

#### 3. **Légende visuelle**
Ajout d'une légende en bas de l'aperçu avec 3 indicateurs :
- 🎬 Zone vidéo (fond gris dégradé)
- 📊 Bande rythmo (couleur personnalisée)
- ⚡ Changement scène (couleur personnalisée)

#### 4. **Améliorations visuelles**
- Fond noir avec dégradé pour simuler un écran plein
- Bordures et ombres pour améliorer la profondeur
- Transitions fluides entre les modes
- Backdrop-filter (flou) pour le mode overlay
- Icône et texte explicatif au-dessus de l'aperçu

## 🎯 Résultat

L'utilisateur peut maintenant :
- ✅ Voir clairement où se trouve la vidéo
- ✅ Comprendre la différence entre les deux modes
- ✅ Visualiser l'impact de chaque paramètre en temps réel
- ✅ Identifier facilement les différentes zones (vidéo, bande, scènes)

## 📐 Détails techniques

### Composant RythmoContent
Création d'un sous-composant dynamique pour afficher le contenu de la bande rythmo :
- Utilise `defineComponent` et `h()` pour le render
- Scale ajustable (0.4 par défaut pour l'aperçu)
- Réutilisable

### Styles CSS
- `.preview-full-screen` : Container principal avec fond noir
- `.preview-video-mock` : Vidéo simulée avec grille et dégradé
- `.preview-rythmo-overlay` : Bande en mode overlay
- `.preview-rythmo-below` : Bande en mode below
- `.preview-legend` : Légende en bas

### Responsive
L'aperçu s'adapte automatiquement à la largeur du container (50% de la modal).

## 🎨 Palette de couleurs utilisée

- **Fond écran** : `#0a0a0a` → `#1a1a1a` (dégradé)
- **Vidéo simulée** : `#2d3748` → `#1a202c` (dégradé)
- **Grille vidéo** : Dégradé violet/rose (`#667eea` → `#764ba2` → `#f093fb`)
- **Bordures** : `#374151`, `#4a5568`
- **Texte légende** : `#d1d5db`

## 📱 Adaptation mobile

L'aperçu reste lisible même sur petits écrans grâce à :
- Aspect ratio 16:9 maintenu
- Texte réduit proportionnellement
- Scale de 0.4 pour tous les éléments
