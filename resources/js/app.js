import './bootstrap';
import { createApp } from 'vue';
import axios from 'axios';
import App from './App.vue';

const mountEl = document.getElementById('app');
const baseUrl = mountEl?.dataset?.baseUrl || window.location.origin;

axios.defaults.baseURL = baseUrl;
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['Accept'] = 'application/json';

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
if (csrfToken) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
}

const app = createApp(App, { baseUrl });
app.mount('#app');
