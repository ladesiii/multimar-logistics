import axios from 'axios'

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'

// Si existe token guardado, lo reutilizamos para las peticiones API.
const storedToken = localStorage.getItem('auth_token')

if (storedToken) {
  axios.defaults.headers.common.Authorization = `Bearer ${storedToken}`
}
