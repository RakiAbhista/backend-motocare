import axios from 'axios';
window.axios = axios;

// Set base URL untuk API
const appUrl = document.querySelector('meta[name="app-url"]')?.content || window.location.origin;
window.axios.defaults.baseURL = appUrl;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Tambahkan token ke header jika sudah ada di localStorage
const token = localStorage.getItem('token');
if (token) {
  window.axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
}
