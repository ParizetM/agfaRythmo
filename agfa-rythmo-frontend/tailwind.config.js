/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        // Couleurs personnalisées basées sur les styles existants
        'agfa-dark': '#2d3748',
        'agfa-gray': '#4a5568',
        'agfa-light': '#f7fafc',
        'agfa-blue': '#3182ce',
        'agfa-blue-hover': '#2563eb',
        'agfa-green': '#38a169',
        'agfa-green-hover': '#2f855a',
        'agfa-red': '#e53e3e',
        'agfa-red-hover': '#c53030',
        // Couleurs du thème sombre AgfaRythmo
        'agfa-bg-primary': '#121827',    // Fond principal (très sombre)
        'agfa-bg-secondary': '#202937',  // Couleur menu/cartes (moins sombre)
        'agfa-bg-tertiary': '#2a3441',   // Couleur pour les cartes (plus claire)
      },
      animation: {
        'fade-in': 'fadeIn 0.3s ease-in-out',
        'slide-up': 'slideUp 0.3s ease-out',
      },
      keyframes: {
        fadeIn: {
          '0%': { opacity: '0' },
          '100%': { opacity: '1' },
        },
        slideUp: {
          '0%': { transform: 'translateY(20px)', opacity: '0' },
          '100%': { transform: 'translateY(0)', opacity: '1' },
        },
      },
    },
  },
  plugins: [],
}
