// Service pour récupérer les polices Google Fonts avec leurs poids disponibles
// https://developers.google.com/fonts/docs/developer_api

export interface GoogleFont {
  family: string
  category: string
  variants: string[] // Les variantes disponibles (ex: ['100', '200', '300', 'regular', '500', etc.])
  weights: number[] // Les poids numériques disponibles (ex: [100, 200, 300, 400, 500, etc.])
}

// Cache des informations de polices
const fontCache = new Map<string, GoogleFont>()

// Fallback statique avec les poids disponibles pour chaque police
const FALLBACK_FONTS: GoogleFont[] = [
  { family: 'Lexend', category: 'sans-serif', variants: ['200', '300', 'regular', '500', '600', '700'], weights: [200, 300, 400, 500, 600, 700] },
  { family: 'Atkinson Hyperlegible', category: 'sans-serif', variants: ['regular', '700'], weights: [400, 700] },
  { family: 'Inter', category: 'sans-serif', variants: ['100', '200', '300', 'regular', '500', '600', '700', '800', '900'], weights: [100, 200, 300, 400, 500, 600, 700, 800, 900] },
  { family: 'Roboto', category: 'sans-serif', variants: ['100', '300', 'regular', '500', '700', '900'], weights: [100, 300, 400, 500, 700, 900] },
  { family: 'Open Sans', category: 'sans-serif', variants: ['300', 'regular', '500', '600', '700', '800'], weights: [300, 400, 500, 600, 700, 800] },
  { family: 'Lato', category: 'sans-serif', variants: ['100', '300', 'regular', '700', '900'], weights: [100, 300, 400, 700, 900] },
  { family: 'Montserrat', category: 'sans-serif', variants: ['100', '200', '300', 'regular', '500', '600', '700', '800', '900'], weights: [100, 200, 300, 400, 500, 600, 700, 800, 900] },
  { family: 'Poppins', category: 'sans-serif', variants: ['100', '200', '300', 'regular', '500', '600', '700', '800', '900'], weights: [100, 200, 300, 400, 500, 600, 700, 800, 900] },
  { family: 'Raleway', category: 'sans-serif', variants: ['100', '200', '300', 'regular', '500', '600', '700', '800', '900'], weights: [100, 200, 300, 400, 500, 600, 700, 800, 900] },
  { family: 'Nunito', category: 'sans-serif', variants: ['200', '300', 'regular', '500', '600', '700', '800', '900'], weights: [200, 300, 400, 500, 600, 700, 800, 900] },
  { family: 'Source Sans Pro', category: 'sans-serif', variants: ['200', '300', 'regular', '600', '700', '900'], weights: [200, 300, 400, 600, 700, 900] },
  { family: 'Ubuntu', category: 'sans-serif', variants: ['300', 'regular', '500', '700'], weights: [300, 400, 500, 700] },
  { family: 'Playfair Display', category: 'serif', variants: ['regular', '500', '600', '700', '800', '900'], weights: [400, 500, 600, 700, 800, 900] },
  { family: 'Merriweather', category: 'serif', variants: ['300', 'regular', '700', '900'], weights: [300, 400, 700, 900] },
  { family: 'PT Serif', category: 'serif', variants: ['regular', '700'], weights: [400, 700] },
  { family: 'Lora', category: 'serif', variants: ['regular', '500', '600', '700'], weights: [400, 500, 600, 700] },
  { family: 'Roboto Slab', category: 'serif', variants: ['100', '200', '300', 'regular', '500', '600', '700', '800', '900'], weights: [100, 200, 300, 400, 500, 600, 700, 800, 900] },
  { family: 'Inconsolata', category: 'monospace', variants: ['200', '300', 'regular', '500', '600', '700', '800', '900'], weights: [200, 300, 400, 500, 600, 700, 800, 900] },
  { family: 'Fira Code', category: 'monospace', variants: ['300', 'regular', '500', '600', '700'], weights: [300, 400, 500, 600, 700] },
  { family: 'JetBrains Mono', category: 'monospace', variants: ['100', '200', '300', 'regular', '500', '600', '700', '800'], weights: [100, 200, 300, 400, 500, 600, 700, 800] },
  { family: 'Source Code Pro', category: 'monospace', variants: ['200', '300', 'regular', '500', '600', '700', '800', '900'], weights: [200, 300, 400, 500, 600, 700, 800, 900] },
]

/**
 * Récupère les informations d'une police depuis le cache ou le fallback
 */
async function fetchFontDetails(fontFamily: string): Promise<GoogleFont | null> {
  // Vérifier le cache
  if (fontCache.has(fontFamily)) {
    return fontCache.get(fontFamily)!
  }

  // Chercher dans le fallback
  const fallback = FALLBACK_FONTS.find(f => f.family === fontFamily)
  if (fallback) {
    fontCache.set(fontFamily, fallback)
    return fallback
  }

  // Si pas de fallback, retourner des poids par défaut
  const defaultFont: GoogleFont = {
    family: fontFamily,
    category: 'sans-serif',
    variants: ['regular', '700'],
    weights: [400, 700],
  }
  fontCache.set(fontFamily, defaultFont)
  return defaultFont
}/**
 * Obtenir les poids disponibles pour une police
 */
export async function getAvailableWeights(fontFamily: string): Promise<number[]> {
  const fontInfo = await fetchFontDetails(fontFamily)
  if (!fontInfo) {
    return [400] // Poids par défaut
  }
  return fontInfo.weights
}

/**
 * Obtenir le poids le plus fin disponible pour une police
 */
export async function getLightestWeight(fontFamily: string): Promise<number> {
  const weights = await getAvailableWeights(fontFamily)
  return weights.length > 0 ? Math.min(...weights) : 400
}

/**
 * Générer l'URL Google Fonts avec tous les poids disponibles
 */
export function getGoogleFontUrl(fontFamily: string, weights?: number[]): string {
  const weightsParam = weights && weights.length > 0
    ? weights.sort((a, b) => a - b).join(';')
    : '100;200;300;400;500;600;700;800;900'

  return `https://fonts.googleapis.com/css2?family=${fontFamily.replace(/ /g, '+')}:wght@${weightsParam}&display=swap`
}

/**
 * Récupère la liste des polices populaires avec leurs informations
 */
export async function fetchGoogleFonts(): Promise<GoogleFont[]> {
  try {
    // Retourner les fallbacks qui contiennent déjà toutes les infos
    return FALLBACK_FONTS
  } catch (error) {
    console.error('Erreur lors de la récupération des polices Google Fonts:', error)
    return FALLBACK_FONTS
  }
}

// Cache des polices déjà chargées pour éviter les duplications
const loadedFonts = new Map<string, Set<number>>()

/**
 * Charge dynamiquement une police Google Fonts avec des poids spécifiques
 * @param fontFamily - Nom de la police à charger
 * @param weights - Poids à charger (optionnel, charge tous les poids par défaut)
 * @returns Promise résolue quand la police est chargée
 */
export function loadGoogleFont(fontFamily: string, weights?: number[]): Promise<void> {
  return new Promise((resolve, reject) => {
    const weightsKey = weights ? weights.join(',') : 'all'

    // Si cette combinaison police+poids est déjà chargée
    if (loadedFonts.has(fontFamily)) {
      const loadedWeights = loadedFonts.get(fontFamily)!
      if (weightsKey === 'all' || (weights && weights.every(w => loadedWeights.has(w)))) {
        resolve()
        return
      }
    }

    // Créer un élément <link> pour charger la police
    const link = document.createElement('link')
    link.rel = 'stylesheet'
    link.href = getGoogleFontUrl(fontFamily, weights)

    // Gérer le chargement réussi
    link.onload = () => {
      if (!loadedFonts.has(fontFamily)) {
        loadedFonts.set(fontFamily, new Set())
      }
      if (weights) {
        weights.forEach(w => loadedFonts.get(fontFamily)!.add(w))
      } else {
        // Tous les poids chargés
        loadedFonts.set(fontFamily, new Set([100, 200, 300, 400, 500, 600, 700, 800, 900]))
      }
      resolve()
    }

    // Gérer les erreurs de chargement
    link.onerror = () => {
      console.error(`Erreur lors du chargement de la police "${fontFamily}"`)
      reject(new Error(`Impossible de charger la police ${fontFamily}`))
    }

    // Ajouter le lien au <head>
    document.head.appendChild(link)
  })
}

/**
 * Charge toutes les polices populaires en arrière-plan
 */
export async function preloadPopularFonts(): Promise<void> {
  try {
    // Charger les polices les plus utilisées en priorité
    const priorityFonts = ['Inter', 'Roboto', 'Open Sans', 'Lato', 'Montserrat']

    // Charger les polices prioritaires en parallèle
    await Promise.all(priorityFonts.map(font => loadGoogleFont(font)))

    // Charger les autres polices en arrière-plan (sans bloquer)
    FALLBACK_FONTS
      .filter((font: GoogleFont) => !priorityFonts.includes(font.family))
      .forEach((font: GoogleFont) => loadGoogleFont(font.family).catch(() => {}))
  } catch (error) {
    console.error('Erreur lors du pré-chargement des polices:', error)
  }
}

/**
 * Obtenir le label lisible pour un poids de police
 */
export function getWeightLabel(weight: number): string {
  switch (weight) {
    case 100: return 'Thin (100)'
    case 200: return 'Extra Light (200)'
    case 300: return 'Light (300)'
    case 400: return 'Regular (400)'
    case 500: return 'Medium (500)'
    case 600: return 'Semi Bold (600)'
    case 700: return 'Bold (700)'
    case 800: return 'Extra Bold (800)'
    case 900: return 'Black (900)'
    default: return `Weight ${weight}`
  }
}
