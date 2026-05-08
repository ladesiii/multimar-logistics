import './bootstrap';
import { createApp } from 'vue';
import Login from './components/paginas/Login.vue';
import Dashboard from './components/paginas/Dashboard.vue';
import EditarPerfil from './components/paginas/EditarPerfil.vue';
import Chatbot from './components/chatbot/chatbot.vue';


// Registramos las páginas Vue que se montan desde las vistas Blade.
const app = createApp({});
app.component('login', Login);
app.component('dashboard', Dashboard);
app.component('editarperfil', EditarPerfil);
app.component('chatbot', Chatbot);
app.mount('#app');
