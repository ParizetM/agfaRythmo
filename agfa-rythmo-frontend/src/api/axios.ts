import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'https://localhost:8000/api',
  withCredentials: false,
});
export default api;
