import './bootstrap';
import { createApp } from 'vue';
import Login from './components/Login.vue';
import Dashboard from './components/Dashboard.vue';


const app = createApp({});
app.component('login', Login);
app.component('dashboard', Dashboard);
app.mount('#app');
