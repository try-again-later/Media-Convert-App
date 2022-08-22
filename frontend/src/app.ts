import { createApp } from 'vue';

import './app.css';
import App from '@root/App.vue';

createApp(App, {
  websocketsServer: import.meta.env.VITE_WEBSOCKETS_SERVER,
  apiServer: import.meta.env.VITE_API_SERVER,
}).mount('#app');
