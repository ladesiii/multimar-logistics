import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const storedToken = localStorage.getItem('auth_token');

if (storedToken) {
	window.axios.defaults.headers.common.Authorization = `Bearer ${storedToken}`;
}
