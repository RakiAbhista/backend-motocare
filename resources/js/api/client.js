import axios from 'axios';

// Create axios instance dengan config default
const apiClient = axios.create({
  baseURL: window.location.origin,
  headers: {
    'X-Requested-With': 'XMLHttpRequest',
    'Content-Type': 'application/json',
  },
  withCredentials: true,
});

// Interceptor: Tambah token dari localStorage
apiClient.interceptors.request.use((config) => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Interceptor: Handle 401/403 errors
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Token expired atau invalid
      localStorage.removeItem('token');
      window.location.href = '/auth/login';
    } else if (error.response?.status === 403) {
      // Forbidden - user tidak punya akses
      console.error('Akses ditolak:', error.response.data);
    }
    return Promise.reject(error);
  }
);

export default apiClient;
