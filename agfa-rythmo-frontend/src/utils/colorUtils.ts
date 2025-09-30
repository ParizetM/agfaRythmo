/**
 * Utilitaires pour la gestion des couleurs des personnages
 */

/**
 * Calcule une couleur de texte lisible basée sur la couleur de fond
 * @param backgroundColor - Couleur de fond au format hex (#RRGGBB)
 * @returns Couleur de texte recommandée (#000000 ou #FFFFFF)
 */
export function getContrastColor(backgroundColor: string): string {
  // Convertir la couleur hex en RGB
  const hex = backgroundColor.replace('#', '')
  const r = parseInt(hex.substr(0, 2), 16)
  const g = parseInt(hex.substr(2, 2), 16)
  const b = parseInt(hex.substr(4, 2), 16)

  // Calculer la luminance
  const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255

  // Retourner blanc ou noir selon la luminance
  return luminance > 0.5 ? '#000000' : '#FFFFFF'
}

/**
 * Suggère une couleur de texte complémentaire et lisible pour une couleur de fond donnée
 * Cette fonction peut être étendue pour proposer d'autres couleurs que juste noir/blanc
 * @param backgroundColor - Couleur de fond au format hex (#RRGGBB)
 * @returns Couleur de texte suggérée
 */
export function suggestTextColor(backgroundColor: string): string {
  return getContrastColor(backgroundColor)
}

/**
 * Vérifie si une couleur est au format hex valide
 * @param color - Couleur à vérifier
 * @returns true si la couleur est valide
 */
export function isValidHexColor(color: string): boolean {
  const hexRegex = /^#[0-9A-Fa-f]{6}$/
  return hexRegex.test(color)
}

/**
 * Normalise une couleur au format hex (ajoute # si manquant, convertit en majuscules)
 * @param color - Couleur à normaliser
 * @returns Couleur normalisée ou null si invalide
 */
export function normalizeHexColor(color: string): string | null {
  if (!color) return null

  let normalized = color.trim()
  if (!normalized.startsWith('#')) {
    normalized = '#' + normalized
  }

  if (!isValidHexColor(normalized)) {
    return null
  }

  return normalized.toUpperCase()
}
