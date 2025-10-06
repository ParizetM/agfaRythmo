/**
 * Utilitaires pour encoder/décoder les positions des séparateurs dans le texte
 * Format utilisé : |$€$position$€$ où position est la valeur flex encodée
 */

export interface SeparatorPositions {
  [separatorIndex: number]: number; // index du séparateur -> valeur flex
}

// Marqueurs pour encoder les positions dans le texte
const POSITION_START = '$€$';
const POSITION_END = '$€$';

/**
 * Encode les positions des séparateurs dans le texte
 * Exemple: "je suis|GRAND|et petit" avec positions [1.5, 0.8]
 * -> "je suis|$€$1.5$€$GRAND|$€$0.8$€$et petit"
 */
export function encodeTextWithPositions(text: string, positions: SeparatorPositions): string {
  if (!text || typeof text !== 'string' || !text.includes('|')) {
    return text || '';
  }

  const segments = text.split('|');
  if (segments.length < 2) {
    return text;
  }

  let result = segments[0];

  for (let i = 1; i < segments.length; i++) {
    const separatorIndex = i - 1;
    const position = positions[separatorIndex];

    result += '|';

    // Ajouter la position encodée si elle existe
    if (position !== undefined && position !== null) {
      result += `${POSITION_START}${position}${POSITION_END}`;
    }

    result += segments[i];
  }

  return result;
}

/**
 * Décode le texte et extrait les positions des séparateurs
 * Exemple: "je suis|$€$1.5$€$GRAND|$€$0.8$€$et petit"
 * -> { text: "je suis|GRAND|et petit", positions: {0: 1.5, 1: 0.8} }
 */
export function decodeTextWithPositions(encodedText: string): {
  text: string;
  positions: SeparatorPositions;
} {
  if (!encodedText || typeof encodedText !== 'string' || !encodedText.includes('|') || !encodedText.includes(POSITION_START)) {
    return {
      text: encodedText || '',
      positions: {}
    };
  }

  const positions: SeparatorPositions = {};
  let cleanText = encodedText;
  let separatorIndex = 0;

  // Regex pour trouver les positions encodées
  const positionRegex = new RegExp(`\\|\\${POSITION_START}([\\d.]+)\\${POSITION_END}`, 'g');

  let match;
  while ((match = positionRegex.exec(encodedText)) !== null) {
    const position = parseFloat(match[1]);
    if (!isNaN(position)) {
      positions[separatorIndex] = position;
    }
    separatorIndex++;
  }

  // Nettoyer le texte en supprimant les positions encodées
  cleanText = encodedText.replace(positionRegex, '|');

  return {
    text: cleanText,
    positions
  };
}

/**
 * Convertit les positions stockées en format compatible avec le composant Vue
 * (Map<timecodeIdx, array de flex values>)
 */
export function convertPositionsToFlexValues(
  timecodeIndex: number,
  text: string,
  positions: SeparatorPositions
): Map<number, number[]> {
  const map = new Map<number, number[]>();

  if (!text.includes('|')) {
    return map;
  }

  const segments = text.split('|');
  const flexValues: number[] = [];

  // Initialiser avec des valeurs par défaut
  for (let i = 0; i < segments.length; i++) {
    flexValues.push(1); // Valeur par défaut
  }

  // Appliquer les positions sauvegardées
  Object.entries(positions).forEach(([sepIndex, position]) => {
    const idx = parseInt(sepIndex);
    if (idx >= 0 && idx < segments.length - 1) {
      // Les positions stockées correspondent aux segments de gauche
      // Il faut reconstituer les flex values pour tous les segments
      flexValues[idx] = position;
    }
  });

  map.set(timecodeIndex, flexValues);
  return map;
}

/**
 * Convertit les flex values du composant Vue en positions à sauvegarder
 */
export function convertFlexValuesToPositions(flexValues: number[]): SeparatorPositions {
  const positions: SeparatorPositions = {};

  for (let i = 0; i < flexValues.length - 1; i++) {
    // Sauvegarder la position de chaque séparateur
    positions[i] = flexValues[i];
  }

  return positions;
}

/**
 * Valide et nettoie les positions des séparateurs
 */
export function validatePositions(positions: SeparatorPositions): SeparatorPositions {
  const cleaned: SeparatorPositions = {};

  Object.entries(positions).forEach(([key, value]) => {
    const numKey = parseInt(key);
    const numValue = parseFloat(value.toString());

    if (!isNaN(numKey) && !isNaN(numValue) && numValue > 0) {
      cleaned[numKey] = Math.max(0.1, Math.min(10, numValue)); // Borner entre 0.1 et 10
    }
  });

  return cleaned;
}
