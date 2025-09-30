# Fonctionnalité Espaces Fixes avec Pipes (|)

## Description

Cette fonctionnalité permet d'ajouter des espaces de largeur fixe dans le texte des timecodes en utilisant le caractère `|` (pipe). Cela permet un meilleur contrôle de la répartition du texte dans les blocs rythmo et une gestion plus fine des prononciations.

## Comment ça fonctionne

### Utilisation de base

Dans le champ texte d'un timecode, utilisez le caractère `|` pour définir des espaces fixes et contrôler les largeurs relatives :

```
Bonjour|le monde
```

### Syntaxe avancée : Largeurs relatives

Vous pouvez maintenant spécifier des multiplicateurs de largeur en utilisant des nombres entre les pipes :

```
mot1|2|mot2
```
→ "mot2" aura 2× la largeur de "mot1"

```
court|3|mot très long|0.5|fin
```
→ "mot très long" sera 3× plus large, "fin" sera 0.5× moins large

### Comportement

- **Pipe simple** (`|`) : Crée un espace de largeur fixe (20px par défaut)
- **Pipe avec nombre** (`|2|`) : Le segment suivant aura le multiplicateur spécifié
- **Largeur relative** : Basée sur des poids relatifs, pas sur la largeur naturelle du texte

### Exemples d'utilisation

#### Exemple 1 : Pause entre mots (comportement classique)
```
Bonjour|monsieur
```
→ "Bonjour" + espace fixe + "monsieur" (largeurs égales)

#### Exemple 2 : Mot important plus large
```
Petit|3|MOT IMPORTANT|normal
```
→ "MOT IMPORTANT" sera 3× plus large que les autres mots

#### Exemple 3 : Contrôle fin des proportions
```
Un|2|deux deux|0.5|fin
```
→ "deux deux" sera 2× plus large, "fin" sera 2× plus petit

#### Exemple 4 : Gestion complexe
```
Intro|4|partie principale du discours|1.5|conclusion
```
→ Partie principale 4× plus large, conclusion 1.5× plus large que l'intro

#### Exemple 5 : Mot court mais important visuellement
```
Le|5|STOP|continue
```
→ "STOP" sera très visible avec 5× la largeur normale

## Configuration technique

### Paramètres configurables

Dans `RythmoBandSingle.vue`, la largeur des espaces fixes est définie par :

```typescript
const PIPE_SPACE_WIDTH = 20 // pixels
```

Vous pouvez modifier cette valeur pour ajuster la largeur des espaces selon vos besoins.

### Calcul des largeurs

1. **Largeur totale disponible** : Largeur du bloc timecode
2. **Largeur des espaces fixes** : `nombre_d'espaces × PIPE_SPACE_WIDTH`
3. **Largeur disponible pour le texte** : `largeur_totale - largeur_espaces_fixes`
4. **Calcul des poids relatifs** : Somme de tous les multiplicateurs
5. **Répartition** : `largeur_segment = (largeur_disponible / total_poids) × multiplicateur_segment`

#### Exemple de calcul
```
"mot1|3|mot2"  dans un bloc de 200px
```
- Largeur totale : 200px
- Espace fixe : 20px 
- Largeur disponible : 180px
- Poids total : 1 + 3 = 4
- Largeur unitaire : 180px / 4 = 45px
- "mot1" : 45px × 1 = 45px
- "mot2" : 45px × 3 = 135px

## Avantages

- **Contrôle précis** : Gestion fine de l'espacement et des largeurs relatives
- **Synchronisation** : Meilleure correspondance avec les temps de prononciation
- **Hiérarchie visuelle** : Possibilité de mettre en valeur certains mots
- **Flexibilité** : Fonctionne avec n'importe quel nombre de segments et multiplicateurs
- **Compatibilité** : Les timecodes sans `|` fonctionnent comme avant
- **Intuitivité** : Syntaxe simple avec des nombres pour les proportions

## Interface utilisateur

Une aide contextuelle est disponible dans le modal d'édition des timecodes pour expliquer l'utilisation des pipes :

> 💡 **Astuce :** Utilisez le caractère `|` pour contrôler l'espacement et les largeurs.  
> • `mot1|mot2` → espaces fixes entre les mots  
> • `mot1|2|mot2` → "mot2" sera 2× plus large que "mot1"

## Cas d'usage typiques

### 1. Mise en valeur d'un mot important
```
Attention|5|DANGER|normal
```
Le mot "DANGER" sera très visible avec 5× la largeur.

### 2. Dialogue avec différents personnages
```
Pierre|0.8|:|1.5|Bonjour Marie
```
"Pierre" plus petit, ":" séparateur, "Bonjour Marie" plus large.

### 3. Timing musical
```
Un|2|deux|3|trois|quatre
```
Chaque temps avec sa propre largeur selon l'importance musicale.

### 4. Sous-titres avec emphase
```
Il|3|FAUT|0.5|que|2|TU SACHES
```
Mots importants ("FAUT", "TU SACHES") plus visibles.