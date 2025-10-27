// Service pour récupérer les polices Google Fonts populaires
// Pour une liste complète, on pourrait utiliser l'API Google Fonts
// https://developers.google.com/fonts/docs/developer_api

export interface GoogleFont {
  family: string
  category: string
}

// Liste de polices populaires pré-sélectionnées pour l'interface
export const POPULAR_FONTS: GoogleFont[] = [
  { family: 'Inter', category: 'sans-serif' },
  { family: 'Roboto', category: 'sans-serif' },
  { family: 'Open Sans', category: 'sans-serif' },
  { family: 'Lato', category: 'sans-serif' },
  { family: 'Montserrat', category: 'sans-serif' },
  { family: 'Poppins', category: 'sans-serif' },
  { family: 'Raleway', category: 'sans-serif' },
  { family: 'Nunito', category: 'sans-serif' },
  { family: 'Source Sans Pro', category: 'sans-serif' },
  { family: 'Ubuntu', category: 'sans-serif' },
  { family: 'Playfair Display', category: 'serif' },
  { family: 'Merriweather', category: 'serif' },
  { family: 'PT Serif', category: 'serif' },
  { family: 'Lora', category: 'serif' },
  { family: 'Roboto Slab', category: 'serif' },
  { family: 'Inconsolata', category: 'monospace' },
  { family: 'Fira Code', category: 'monospace' },
  { family: 'JetBrains Mono', category: 'monospace' },
  { family: 'Source Code Pro', category: 'monospace' },
]

export async function fetchGoogleFonts(): Promise<GoogleFont[]> {
  try {
    // Pour l'instant, on retourne les polices populaires
    // Pour utiliser l'API réelle, décommentez les lignes ci-dessous et ajoutez votre clé API Google Fonts :
    // const GOOGLE_FONTS_API_KEY = 'VOTRE_CLE_API'
    // const response = await fetch(`https://www.googleapis.com/webfonts/v1/webfonts?key=${GOOGLE_FONTS_API_KEY}&sort=popularity`)
    // const data = await response.json()
    // return data.items.map((item: any) => ({ family: item.family, category: item.category }))

    return POPULAR_FONTS
  } catch (error) {
    console.error('Erreur lors de la récupération des polices Google Fonts:', error)
    return POPULAR_FONTS
  }
}

export function getGoogleFontUrl(fontFamily: string): string {
  return `https://fonts.googleapis.com/css2?family=${fontFamily.replace(/ /g, '+')}:wght@400;600;700&display=swap`
}

// Cache des polices déjà chargées pour éviter les duplications
const loadedFonts = new Set<string>()

/**
 * Charge dynamiquement une police Google Fonts dans le DOM
 * @param fontFamily - Nom de la police à charger
 * @returns Promise résolue quand la police est chargée
 */
export function loadGoogleFont(fontFamily: string): Promise<void> {
  return new Promise((resolve, reject) => {
    // Si la police est déjà chargée, on résout immédiatement
    if (loadedFonts.has(fontFamily)) {
      resolve()
      return
    }

    // Créer un élément <link> pour charger la police
    const link = document.createElement('link')
    link.rel = 'stylesheet'
    link.href = getGoogleFontUrl(fontFamily)

    // Gérer le chargement réussi
    link.onload = () => {
      loadedFonts.add(fontFamily)
      // console.log(`Police "${fontFamily}" chargée avec succès`)
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
 * Utile pour pré-charger les polices au démarrage de l'app
 */
export async function preloadPopularFonts(): Promise<void> {
  try {
    // Charger les polices les plus utilisées en priorité
    const priorityFonts = ['Inter', 'Roboto', 'Open Sans', 'Lato', 'Montserrat']

    // Charger les polices prioritaires en parallèle
    await Promise.all(priorityFonts.map(font => loadGoogleFont(font)))

    // Charger les autres polices en arrière-plan (sans bloquer)
    POPULAR_FONTS
      .filter(font => !priorityFonts.includes(font.family))
      .forEach(font => loadGoogleFont(font.family).catch(() => {}))
  } catch (error) {
    console.error('Erreur lors du pré-chargement des polices:', error)
  }
}
