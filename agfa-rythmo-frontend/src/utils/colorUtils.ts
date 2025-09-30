/**
 * Utilitaires pour la gestion des couleurs des personnages
 * Support des formats : hex (#RRGGBB, #RGB), rgba(r, g, b, a), rgb(r, g, b)
 */

interface RGBAColor {
  r: number
  g: number
  b: number
  a: number
}

/**
 * Parse une couleur en format string vers RGBA
 * @param color - Couleur au format hex, rgb ou rgba
 * @returns Objet RGBA ou null si invalide
 */
function parseColor(color: string): RGBAColor | null {
  if (!color) return null
  
  const trimmed = color.trim()
  
  // Format hex (#RRGGBB ou #RGB)
  if (trimmed.startsWith('#')) {
    const hex = trimmed.substring(1)
    
    if (hex.length === 3) {
      // Format #RGB -> #RRGGBB
      const r = parseInt(hex[0] + hex[0], 16)
      const g = parseInt(hex[1] + hex[1], 16)
      const b = parseInt(hex[2] + hex[2], 16)
      return { r, g, b, a: 1 }
    } else if (hex.length === 6) {
      // Format #RRGGBB
      const r = parseInt(hex.substr(0, 2), 16)
      const g = parseInt(hex.substr(2, 2), 16)
      const b = parseInt(hex.substr(4, 2), 16)
      return { r, g, b, a: 1 }
    }
    return null
  }
  
  // Format rgb() ou rgba()
  const rgbaMatch = trimmed.match(/rgba?\(\s*(\d+(?:\.\d+)?)\s*,\s*(\d+(?:\.\d+)?)\s*,\s*(\d+(?:\.\d+)?)\s*(?:,\s*(\d+(?:\.\d+)?))?\s*\)/)
  if (rgbaMatch) {
    const r = Math.round(parseFloat(rgbaMatch[1]))
    const g = Math.round(parseFloat(rgbaMatch[2]))
    const b = Math.round(parseFloat(rgbaMatch[3]))
    const a = rgbaMatch[4] ? parseFloat(rgbaMatch[4]) : 1
    
    if (r >= 0 && r <= 255 && g >= 0 && g <= 255 && b >= 0 && b <= 255 && a >= 0 && a <= 1) {
      return { r, g, b, a }
    }
  }
  
  return null
}

/**
 * Calcule une couleur de texte lisible basée sur la couleur de fond
 * @param backgroundColor - Couleur de fond (hex, rgb, rgba)
 * @returns Couleur de texte recommandée (hex)
 */
export function getContrastColor(backgroundColor: string): string {
  const color = parseColor(backgroundColor)
  if (!color) return '#FFFFFF' // Par défaut blanc si couleur invalide
  
  // Calculer la luminance relative
  const getLuminance = (c: number) => {
    c = c / 255
    return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4)
  }
  
  const luminance = 0.2126 * getLuminance(color.r) + 0.7152 * getLuminance(color.g) + 0.0722 * getLuminance(color.b)
  
  // Retourner blanc ou noir selon la luminance
  return luminance > 0.179 ? '#000000' : '#FFFFFF'
}

/**
 * Suggère une couleur de texte complémentaire et lisible pour une couleur de fond donnée
 * @param backgroundColor - Couleur de fond (hex, rgb, rgba)
 * @returns Couleur de texte suggérée
 */
export function suggestTextColor(backgroundColor: string): string {
  return getContrastColor(backgroundColor)
}

/**
 * Vérifie si une couleur est valide (hex, rgb ou rgba)
 * @param color - Couleur à vérifier
 * @returns true si la couleur est valide
 */
export function isValidColor(color: string): boolean {
  return parseColor(color) !== null
}

/**
 * Vérifie si une couleur est au format hex valide
 * @param color - Couleur à vérifier
 * @returns true si la couleur est valide
 */
export function isValidHexColor(color: string): boolean {
  const hexRegex = /^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/
  return hexRegex.test(color)
}

/**
 * Convertit une couleur vers le format RGBA string
 * @param color - Couleur source (hex, rgb, rgba)
 * @param alpha - Opacité optionnelle (0-1)
 * @returns Couleur au format rgba() ou null si invalide
 */
export function toRGBA(color: string, alpha?: number): string | null {
  const parsed = parseColor(color)
  if (!parsed) return null
  
  const finalAlpha = alpha !== undefined ? alpha : parsed.a
  return `rgba(${parsed.r}, ${parsed.g}, ${parsed.b}, ${finalAlpha})`
}

/**
 * Convertit une couleur vers le format hex
 * @param color - Couleur source (hex, rgb, rgba)
 * @returns Couleur au format #RRGGBB ou null si invalide
 */
export function toHex(color: string): string | null {
  const parsed = parseColor(color)
  if (!parsed) return null
  
  const toHexComponent = (c: number) => Math.round(c).toString(16).padStart(2, '0')
  return `#${toHexComponent(parsed.r)}${toHexComponent(parsed.g)}${toHexComponent(parsed.b)}`
}

/**
 * Génère une couleur avec transparence basée sur une couleur de base
 * @param baseColor - Couleur de base
 * @param opacity - Opacité (0-1)
 * @returns Couleur avec transparence
 */
export function withOpacity(baseColor: string, opacity: number): string {
  return toRGBA(baseColor, Math.max(0, Math.min(1, opacity))) || baseColor
}

/**
 * Génère des couleurs prédéfinies avec transparence pour les personnages
 */
export const predefinedColors = [
  'rgba(59, 130, 246, 0.8)',   // Bleu
  'rgba(16, 185, 129, 0.8)',   // Vert
  'rgba(245, 101, 101, 0.8)',  // Rouge
  'rgba(251, 191, 36, 0.8)',   // Jaune
  'rgba(168, 85, 247, 0.8)',   // Violet
  'rgba(236, 72, 153, 0.8)',   // Rose
  'rgba(20, 184, 166, 0.8)',   // Teal
  'rgba(249, 115, 22, 0.8)',   // Orange
  'rgba(139, 69, 19, 0.8)',    // Marron
  'rgba(75, 85, 99, 0.8)',     // Gris
]

/**
 * Normalise une couleur (convertit vers un format standard)
 * @param color - Couleur à normaliser
 * @returns Couleur normalisée ou null si invalide
 */
export function normalizeColor(color: string): string | null {
  const parsed = parseColor(color)
  if (!parsed) return null
  
  // Si opaque, retourner en hex, sinon en rgba
  if (parsed.a === 1) {
    return toHex(color)
  } else {
    return toRGBA(color)
  }
}