import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Si existe token guardado, lo reutilizamos para las peticiones API.
const storedToken = localStorage.getItem('auth_token');

if (storedToken) {
	window.axios.defaults.headers.common.Authorization = `Bearer ${storedToken}`;
}
