import './bootstrap';
import { createApp } from 'vue';
import Navbar from './components/Navbar.vue';
import Login from './components/Login.vue';
import Tracking from './components/Tracking.vue';

const app = createApp({});
app.component('navbar', Navbar);
app.component('login', Login);
app.component('tracking', Tracking);
app.mount('#app');
