import './bootstrap';
import { createApp } from 'vue';
import Navbar from './components/Navbar.vue';
import Login from './components/Login.vue';

const app = createApp({});
app.component('navbar', Navbar);
app.component('login', Login);
app.mount('#app');
