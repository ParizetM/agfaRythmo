import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
  withCredentials: false,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  }
});

// Intercepteur pour ajouter le token d'authentification
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token');
  if (token) {
    config.headers['Authorization'] = `Bearer ${token}`;
  }
  return config;
});

// Intercepteur pour gérer les erreurs d'authentification
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Token invalide ou expiré
      localStorage.removeItem('auth_token');
      delete api.defaults.headers.common['Authorization'];

      // Rediriger vers la page de connexion si pas déjà en cours
      if (window.location.pathname !== '/login') {
        window.location.href = '/login';
      }
    }

    // Améliorer le message d'erreur pour le développement
    if (error.response?.data) {
      const errorData = error.response.data;
      let errorMessage = error.message;

      if (errorData.message) {
        errorMessage = errorData.message;
      } else if (errorData.errors) {
        // Erreurs de validation Laravel
        const validationErrors = Object.values(errorData.errors).flat();
        errorMessage = validationErrors.join(', ');
      }

      // Créer une nouvelle erreur avec le message amélioré
      const enhancedError = new Error(errorMessage);
      enhancedError.name = error.name;
      Object.assign(enhancedError, { response: error.response, config: error.config });

      return Promise.reject(enhancedError);
    }

    return Promise.reject(error);
  }
);

export default api;
