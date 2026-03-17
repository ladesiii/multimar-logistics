import './bootstrap';
import { createApp } from 'vue';
import Navbar from './components/Navbar.vue';

const app = createApp({});
app.component('navbar', Navbar);
app.mount('#app');
